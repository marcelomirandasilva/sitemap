<?php

namespace App\Http\Controllers;

use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DownloadController extends Controller
{
    protected SitemapGeneratorService $sitemapService;

    public function __construct(SitemapGeneratorService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Proxy para download de sitemaps da API Python.
     */
    public function sitemap(string $jobId, string $filename)
    {
        // TODO: Em produção, verificar se o usuário atual é dono do projeto vinculado ao $jobId

        // Caminho base para os sitemaps da API Python (assumindo estrutura irmã)
        $basePath = base_path('../api-sitemap/sitemaps/' . $jobId . '/');
        $filePath = $basePath . $filename;

        // Fallback para .zip se o arquivo original não existir (comum na API Python)
        if (!file_exists($filePath) && !str_ends_with($filename, '.zip')) {
            if (file_exists($filePath . '.zip')) {
                $filePath .= '.zip';
                $filename .= '.zip';
            }
        }

        if (!file_exists($filePath)) {
            // Se falhar no acesso direto, tentamos um último esforço via API (caso mude o ambiente)
            $response = $this->sitemapService->getArtifactFile($jobId, $filename);
            if ($response && $response->successful()) {
                $contentType = $response->header('Content-Type') ?: 'application/xml';
                return Response::make($response->body(), 200, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            }

            abort(404, "Arquivo sitemap não encontrado no sistema ou na API.");
        }

        $contentType = str_ends_with($filename, '.zip') ? 'application/zip' : 'application/xml';

        return Response::file($filePath, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
