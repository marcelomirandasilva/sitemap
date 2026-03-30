<?php

namespace App\Http\Controllers;

use App\Models\TarefaSitemap;
use App\Services\SitemapGeneratorService;
use Illuminate\Support\Facades\Response;

class DownloadController extends Controller
{
    protected SitemapGeneratorService $sitemapService;

    public function __construct(SitemapGeneratorService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    protected function authorizeJobDownload(TarefaSitemap $job): void
    {
        if (!$job->projeto || $job->projeto->user_id !== auth()->id()) {
            abort(403, 'Voce nao tem permissao para acessar este arquivo.');
        }
    }

    protected function normalizeFilename(string $filename): string
    {
        $normalized = basename($filename);

        if ($normalized !== $filename || str_contains($normalized, '..')) {
            abort(404, 'Arquivo invalido.');
        }

        return $normalized;
    }

    protected function resolveLocalFilePath(TarefaSitemap $job, string $filename): array
    {
        $candidates = [
            [$filename, base_path('../api-sitemap/sitemaps/' . $job->external_job_id . '/' . $filename)],
            [$filename, base_path('../api-sitemap/sitemaps/projects/' . $job->project_id . '/' . $filename)],
        ];

        if (!str_ends_with($filename, '.zip')) {
            $zipName = $filename . '.zip';
            $candidates[] = [$zipName, base_path('../api-sitemap/sitemaps/' . $job->external_job_id . '/' . $zipName)];
            $candidates[] = [$zipName, base_path('../api-sitemap/sitemaps/projects/' . $job->project_id . '/' . $zipName)];
        }

        foreach ($candidates as [$resolvedName, $path]) {
            if (file_exists($path)) {
                return [$resolvedName, $path];
            }
        }

        return [$filename, null];
    }

    /**
     * Proxy para download de sitemaps da API Python.
     */
    public function sitemap(string $jobId, string $filename)
    {
        $job = TarefaSitemap::with('projeto')
            ->where('external_job_id', $jobId)
            ->firstOrFail();

        $this->authorizeJobDownload($job);

        $filename = $this->normalizeFilename($filename);
        [$resolvedFilename, $filePath] = $this->resolveLocalFilePath($job, $filename);

        if (!$filePath) {
            $remoteCandidates = [$filename];

            if (!str_ends_with($filename, '.zip')) {
                $remoteCandidates[] = $filename . '.zip';
            }

            foreach ($remoteCandidates as $remoteFilename) {
                $response = $this->sitemapService->getArtifactFile($jobId, $remoteFilename, auth()->id());

                if ($response && $response->successful()) {
                    $contentType = $response->header('Content-Type') ?: 'application/xml';

                    return Response::make($response->body(), 200, [
                        'Content-Type' => $contentType,
                        'Content-Disposition' => 'attachment; filename="' . $remoteFilename . '"',
                    ]);
                }
            }

            abort(404, 'Arquivo sitemap nao encontrado no sistema ou na API.');
        }

        $contentType = str_ends_with($resolvedFilename, '.zip') ? 'application/zip' : 'application/xml';

        return Response::file($filePath, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $resolvedFilename . '"',
        ]);
    }
}
