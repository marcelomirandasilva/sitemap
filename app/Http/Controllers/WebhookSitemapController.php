<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSitemapArtifactsJob;
use App\Models\TarefaSitemap;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Recebe notificacoes da API Python sobre conclusao ou falha de jobs.
 * Rota: POST /api/internal/webhook/job-completed
 *
 * A autenticacao e feita via X-Internal-Token (mesmo segredo compartilhado).
 */
class WebhookSitemapController extends Controller
{
    public function __construct(protected SitemapGeneratorService $sitemapService)
    {
    }

    /**
     * Processa o aviso de conclusao de job enviado pela API Python.
     */
    public function jobCompleted(Request $request)
    {
        $secret = config('services.sitemap_generator.internal_secret');

        if (empty($secret) || $request->header('X-Internal-Token') !== $secret) {
            Log::warning('Webhook rejeitado: token invalido ou ausente.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'job_id' => 'required|string',
            'status' => 'required|string|in:completed,failed,cancelled',
        ]);

        $externalJobId = $request->string('job_id')->toString();
        $status = $request->string('status')->toString();
        $pagesFound = (int) $request->input('pages_found', 0);
        $errorMessage = $request->input('error_message');

        $job = TarefaSitemap::with('projeto.user')
            ->where('external_job_id', $externalJobId)
            ->first();

        if (!$job) {
            Log::warning("Webhook recebido para job_id desconhecido: {$externalJobId}");

            return response()->json(['message' => 'Job nao encontrado, ignorado.']);
        }

        $userId = $job->projeto?->user_id;
        $statusData = null;

        if ($userId) {
            $statusData = $this->sitemapService->checkStatus($externalJobId, $userId);
        }

        $resolvedStatus = $statusData['status'] ?? $status;

        $updateData = [
            'status' => $resolvedStatus,
            'message' => $statusData['message'] ?? $errorMessage ?? $job->message,
        ];

        if ($resolvedStatus === 'completed') {
            $updateData['progress'] = 100;
            $updateData['completed_at'] = now();
        }

        if (in_array($resolvedStatus, ['failed', 'cancelled'], true)) {
            $updateData['completed_at'] = now();
        }

        if ($statusData) {
            $updateData['pages_count'] = $statusData['result']['total_urls'] ?? $statusData['urls_found'] ?? $job->pages_count ?? $pagesFound;
            $updateData['urls_found'] = $statusData['result']['total_urls'] ?? $statusData['urls_found'] ?? $job->urls_found ?? $pagesFound;
            $updateData['urls_crawled'] = $statusData['urls_crawled'] ?? $job->urls_crawled;
            $updateData['urls_excluded'] = $statusData['urls_excluded'] ?? $job->urls_excluded;
            $updateData['images_count'] = $statusData['result']['total_images'] ?? $statusData['images_found'] ?? $job->images_count ?? 0;
            $updateData['videos_count'] = $statusData['result']['total_videos'] ?? $statusData['videos_found'] ?? $job->videos_count ?? 0;

            if (!empty($statusData['artifacts'])) {
                $updateData['artifacts'] = $statusData['artifacts'];
            }
        } elseif ($pagesFound > 0) {
            $updateData['pages_count'] = max($job->pages_count ?? 0, $pagesFound);
            $updateData['urls_found'] = max($job->urls_found ?? 0, $pagesFound);
        }

        $job->update($updateData);
        $job->refresh();

        if ($resolvedStatus === 'completed' && $job->projeto) {
            $job->projeto->update([
                'last_crawled_at' => now(),
                'status' => 'active',
            ]);

            ProcessSitemapArtifactsJob::dispatch($job);
        }

        $this->notifyUserCallback($job, $statusData, $errorMessage);

        Log::info('Webhook processado com sucesso.', [
            'job_id' => $externalJobId,
            'status' => $resolvedStatus,
            'project_id' => $job->project_id,
            'user_id' => $job->projeto?->user_id,
        ]);

        return response()->json(['message' => 'Webhook processado com sucesso.']);
    }

    protected function notifyUserCallback(TarefaSitemap $job, ?array $statusData = null, ?string $errorMessage = null): void
    {
        $callbackUrl = $job->projeto?->user?->api_callback_url;

        if (!$callbackUrl) {
            return;
        }

        $externalApiBase = rtrim(config('services.sitemap_generator.base_url'), '/') . '/api/v1';
        $artifacts = $this->buildExternalArtifacts($job, $statusData, $externalApiBase);

        $payload = [
            'event' => 'sitemap.job.' . $job->status,
            'job_id' => $job->external_job_id,
            'status' => $job->status,
            'message' => $statusData['message'] ?? $job->message,
            'project' => [
                'id' => $job->project_id,
                'url' => $job->projeto?->url,
            ],
            'counts' => [
                'pages_count' => $job->pages_count ?? 0,
                'urls_found' => $job->urls_found ?? $job->pages_count ?? 0,
                'urls_crawled' => $job->urls_crawled ?? 0,
                'urls_excluded' => $job->urls_excluded ?? 0,
                'images_count' => $job->images_count ?? 0,
                'videos_count' => $job->videos_count ?? 0,
            ],
            'links' => [
                'status' => $externalApiBase . '/sitemaps/' . $job->external_job_id,
                'artifacts' => $externalApiBase . '/sitemaps/' . $job->external_job_id . '/artifacts',
            ],
            'artifacts' => $artifacts,
            'error_message' => $statusData['error_message'] ?? $errorMessage,
            'completed_at' => optional($job->completed_at)->toIso8601String(),
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(5)
                ->withHeaders([
                    'X-Sitemap-Event' => $payload['event'],
                    'X-Sitemap-Job-Id' => $job->external_job_id,
                    'X-Sitemap-Project-Id' => (string) $job->project_id,
                    'User-Agent' => 'sitemap-laravel-callback/1.0',
                ])
                ->post($callbackUrl, $payload);

            if (!$response->successful()) {
                Log::warning('Callback do usuario retornou erro.', [
                    'job_id' => $job->external_job_id,
                    'status' => $response->status(),
                    'callback_url' => $callbackUrl,
                    'response' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar callback do usuario.', [
                'job_id' => $job->external_job_id,
                'callback_url' => $callbackUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function buildExternalArtifacts(TarefaSitemap $job, ?array $statusData, string $externalApiBase): array
    {
        $rawArtifacts = $statusData['artifacts'] ?? $job->artifacts ?? [];

        return collect($rawArtifacts)
            ->map(function ($artifact) use ($job, $externalApiBase) {
                $name = $artifact['name'] ?? basename($artifact['path'] ?? '');

                if (!$name) {
                    return null;
                }

                return [
                    'name' => $name,
                    'type' => $artifact['type'] ?? null,
                    'size_bytes' => $artifact['size_bytes'] ?? ($artifact['size'] ?? 0),
                    'download_url' => $externalApiBase . '/sitemaps/' . $job->external_job_id . '/artifacts/' . rawurlencode($name),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
