<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use App\Models\Projeto;
use App\Models\TarefaSitemap;
use App\Services\ExecucaoRastreamentoService;
use App\Services\FrequenciaRastreamentoService;
use App\Services\RelatorioSeoBilingueService;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RastreadorController extends Controller
{
    protected $sitemapService;
    protected $relatorioSeoBilingue;
    protected $frequenciaRastreamento;
    protected $execucaoRastreamento;

    public function __construct(
        SitemapGeneratorService $sitemapService,
        RelatorioSeoBilingueService $relatorioSeoBilingue,
        FrequenciaRastreamentoService $frequenciaRastreamento,
        ExecucaoRastreamentoService $execucaoRastreamento
    )
    {
        $this->sitemapService = $sitemapService;
        $this->relatorioSeoBilingue = $relatorioSeoBilingue;
        $this->frequenciaRastreamento = $frequenciaRastreamento;
        $this->execucaoRastreamento = $execucaoRastreamento;
    }

    protected function syncJobFromStatus(TarefaSitemap $job, array $statusData): TarefaSitemap
    {
        $job->update([
            'status' => $statusData['status'] ?? $job->status,
            'progress' => $statusData['progress'] ?? $job->progress,
            'pages_count' => $statusData['result']['total_urls']
                ?? $statusData['urls_found']
                ?? $statusData['pages_count']
                ?? $job->pages_count,
            'urls_found' => $statusData['result']['total_urls']
                ?? $statusData['urls_found']
                ?? $statusData['pages_count']
                ?? $job->urls_found,
            'urls_crawled' => $statusData['urls_crawled'] ?? $job->urls_crawled,
            'urls_excluded' => $statusData['urls_excluded'] ?? $job->urls_excluded,
            'images_count' => $statusData['result']['total_images']
                ?? $statusData['images_found']
                ?? $job->images_count,
            'videos_count' => $statusData['result']['total_videos']
                ?? $statusData['videos_found']
                ?? $job->videos_count,
            'message' => $statusData['message'] ?? $statusData['error_message'] ?? $job->message,
            'artifacts' => $statusData['artifacts'] ?? $job->artifacts ?? [],
            'started_at' => $statusData['started_at'] ?? $job->started_at,
            'completed_at' => $statusData['completed_at'] ?? $job->completed_at,
        ]);

        return $job->refresh();
    }

    protected function finalizeCompletedJob(Projeto $projeto, TarefaSitemap $job): void
    {
        if (!$job->completed_at) {
            $job->update(['completed_at' => now()]);
            $job->refresh();
        }

        $updates = [
            'last_crawled_at' => $job->completed_at ?? now(),
            'status' => 'active',
        ];

        if ($projeto->current_crawler_job_id === $job->external_job_id) {
            $updates['current_crawler_job_id'] = null;
        }

        $projeto->update($updates);
        $this->frequenciaRastreamento->atualizarProximoRastreamento(
            $projeto->refresh(),
            $job->completed_at ?? now(),
            $projeto->user?->planoEfetivo()
        );
    }

    protected function finalizeTerminalJob(Projeto $projeto, TarefaSitemap $job): void
    {
        if ($projeto->current_crawler_job_id === $job->external_job_id) {
            $projeto->update(['current_crawler_job_id' => null]);
        }
    }

    /**
     * Inicia um novo processo de rastreamento para o projeto.
     */
    public function store(Request $request, Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $jobAtivo = $this->execucaoRastreamento->localizarJobAtivo($projeto);

            if ($jobAtivo) {
                $statusData = $this->sitemapService->checkStatus($jobAtivo->external_job_id, auth()->id());

                if ($statusData) {
                    $statusAnterior = $jobAtivo->status;
                    $jobAtivo = $this->syncJobFromStatus($jobAtivo, $statusData);

                    if ($jobAtivo->status === 'completed') {
                        if (empty($jobAtivo->artifacts)) {
                            $jobAtivo->update([
                                'artifacts' => $this->sitemapService->getArtifacts($jobAtivo->external_job_id, auth()->id()),
                            ]);
                            $jobAtivo->refresh();
                        }

                        if ($statusAnterior !== 'completed') {
                            \App\Jobs\ProcessSitemapArtifactsJob::dispatch($jobAtivo);
                        }

                        $this->finalizeCompletedJob($projeto, $jobAtivo);
                    } elseif (in_array($jobAtivo->status, ['failed', 'cancelled'])) {
                        $this->finalizeTerminalJob($projeto, $jobAtivo);
                    }
                }
            }

            $jobAtivo = $this->execucaoRastreamento->localizarJobAtivo($projeto);

            if ($jobAtivo) {
                return response()->json([
                    'message' => 'Ja existe um rastreamento em andamento para este projeto.',
                    'job_id' => $jobAtivo->external_job_id,
                    'status' => $jobAtivo->status,
                ], 409);
            }

            $resultado = $this->execucaoRastreamento->iniciar(
                $projeto,
                'Job criado e aguardando processamento.'
            );

            if (!$resultado['success']) {
                return response()->json(['message' => 'Falha ao iniciar o servico de crawler. Tente novamente.'], 500);
            }

            return response()->json([
                'message' => 'Rastreamento iniciado!',
                'job_id' => $resultado['job_id'],
                'status' => 'queued',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erro no RastreadorController@store', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Ocorreu um erro interno ao iniciar o rastreamento. Tente novamente em instantes.',
            ], 500);
        }
    }

    /**
     * Cancela o job ativo do projeto.
     */
    public function cancel(Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        $jobAtivo = $this->execucaoRastreamento->localizarJobAtivo($projeto);

        if (!$jobAtivo) {
            return response()->json([
                'message' => 'Nao ha rastreamento ativo para cancelar.',
            ], 422);
        }

        $resultado = $this->sitemapService->cancelJob(
            $jobAtivo->external_job_id,
            auth()->id(),
            $projeto->id
        );

        if (!$resultado['success']) {
            $statusData = $this->sitemapService->checkStatus($jobAtivo->external_job_id, auth()->id());

            if ($statusData) {
                $jobAtivo = $this->syncJobFromStatus($jobAtivo, $statusData);

                if ($jobAtivo->status === 'completed') {
                    $this->finalizeCompletedJob($projeto, $jobAtivo);
                } elseif (in_array($jobAtivo->status, ['failed', 'cancelled'])) {
                    $this->finalizeTerminalJob($projeto, $jobAtivo);
                }
            }

            return response()->json([
                'message' => $resultado['message'] ?? 'Nao foi possivel cancelar o rastreamento.',
                'status' => $jobAtivo->status,
                'job_id' => $jobAtivo->external_job_id,
            ], 409);
        }

        $jobAtivo->update([
            'status' => 'cancelled',
            'message' => $resultado['message'] ?? 'Rastreamento cancelado com sucesso.',
            'completed_at' => $resultado['cancelled_at'] ?? now(),
        ]);
        $jobAtivo->refresh();

        $this->finalizeTerminalJob($projeto, $jobAtivo);

        return response()->json([
            'message' => $jobAtivo->message,
            'status' => $jobAtivo->status,
            'job_id' => $jobAtivo->external_job_id,
            'completed_at' => $jobAtivo->completed_at?->toISOString(),
        ]);
    }

    /**
     * Verifica e atualiza o status do ultimo job do projeto.
     */
    public function getStatus(Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        $ultimoJob = $projeto->tarefasSitemap()->latest()->first();

        if (!$ultimoJob) {
            return response()->json([
                'status' => null,
                'progress' => 0,
                'message' => 'Nenhum rastreamento iniciado ainda.',
            ]);
        }

        $shouldCheckApi = !in_array($ultimoJob->status, ['completed', 'failed', 'cancelled'])
            || empty($ultimoJob->artifacts)
            || is_null($ultimoJob->urls_found)
            || is_null($ultimoJob->urls_crawled)
            || is_null($ultimoJob->urls_excluded);

        $statusData = [];

        if ($shouldCheckApi) {
            Log::info("RastreadorController: Consultando API para job {$ultimoJob->external_job_id}");
            $statusData = $this->sitemapService->checkStatus($ultimoJob->external_job_id, auth()->id());

            if ($statusData) {
                Log::info('RastreadorController: Resposta recebida da API', $statusData);

                $statusAnterior = $ultimoJob->status;
                $ultimoJob = $this->syncJobFromStatus($ultimoJob, $statusData);

                if ($ultimoJob->status === 'completed') {
                    if (empty($ultimoJob->artifacts)) {
                        $artifacts = $this->sitemapService->getArtifacts($ultimoJob->external_job_id, auth()->id());

                        $ultimoJob->update([
                            'artifacts' => $artifacts,
                        ]);
                        $ultimoJob->refresh();
                    }

                    $this->finalizeCompletedJob($projeto, $ultimoJob);

                    if ($statusAnterior !== 'completed') {
                        \App\Jobs\ProcessSitemapArtifactsJob::dispatch($ultimoJob);
                    }
                } elseif (in_array($ultimoJob->status, ['failed', 'cancelled'])) {
                    $this->finalizeTerminalJob($projeto, $ultimoJob);
                }
            }
        }

        if ($ultimoJob->status === 'completed') {
            $this->finalizeCompletedJob($projeto, $ultimoJob);
        } elseif (in_array($ultimoJob->status, ['failed', 'cancelled'])) {
            $this->finalizeTerminalJob($projeto, $ultimoJob);
        }

        $previewUrls = [];

        if ($ultimoJob->status === 'completed') {
            $previewUrls = $this->sitemapService->getPreviewUrls($ultimoJob->artifacts ?? [], $ultimoJob->external_job_id);
            Log::info("RastreadorController: Gerado preview para job {$ultimoJob->external_job_id}. Total URLs: " . count($previewUrls));
        }

        $seoBilingue = null;

        if ($ultimoJob->status === 'completed') {
            $seoBilingue = $this->relatorioSeoBilingue->montarParaProjeto($projeto);
        }

        $projeto->refresh();

        return response()->json([
            'external_job_id' => $ultimoJob->external_job_id,
            'status' => $ultimoJob->status,
            'progress' => $ultimoJob->progress,
            'message' => $ultimoJob->message ?? null,
            'pages_count' => $ultimoJob->pages_count,
            'urls_crawled' => $statusData['urls_crawled'] ?? $ultimoJob->urls_crawled ?? $ultimoJob->pages_count ?? 0,
            'urls_found' => $statusData['result']['total_urls'] ?? $statusData['urls_found'] ?? $ultimoJob->urls_found ?? $ultimoJob->pages_count ?? 0,
            'urls_excluded' => $statusData['urls_excluded'] ?? $ultimoJob->urls_excluded ?? 0,
            'images_count' => $ultimoJob->images_count ?? 0,
            'videos_count' => $ultimoJob->videos_count ?? 0,
            'artifacts' => $ultimoJob->artifacts,
            'preview_urls' => $previewUrls,
            'recent_pages' => $statusData['recent_pages'] ?? [],
            'current_url' => $statusData['current_url'] ?? null,
            'current_depth' => $statusData['current_depth'] ?? 0,
            'queue_size' => $statusData['queue_size'] ?? 0,
            'started_at' => $ultimoJob->started_at?->toISOString(),
            'completed_at' => $ultimoJob->completed_at?->toISOString(),
            'next_scheduled_crawl_at' => $projeto->next_scheduled_crawl_at?->toISOString(),
            'seo_bilingue' => $seoBilingue,
        ]);
    }

    /**
     * Retorna paginated URLs a partir do arquivo sitemap/txt.
     */
    public function getUrls(Request $request, Projeto $projeto)
    {
        try {
            if ($projeto->user_id !== auth()->id()) {
                abort(403);
            }

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 50);
            $search = $request->input('q');

            $query = Pagina::where('project_id', $projeto->id);

            if ($search) {
                $query->where('url', 'like', '%' . $search . '%');
            }

            $total = $query->count();
            $records = $query->orderBy('id')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function ($pagina) {
                    return [
                        'url' => $pagina->url,
                        'title' => $pagina->title,
                        'status_code' => $pagina->status_code,
                        'language' => $pagina->language,
                        'canonicalUrl' => $pagina->canonical_url,
                        'hreflangCount' => count($pagina->hreflang_links ?? []),
                        'content_type' => $pagina->content_type,
                        'size_bytes' => $pagina->size_bytes,
                        'lastMod' => $pagina->updated_at->toIso8601String(),
                        'priority' => $pagina->priority ?? '0.5',
                        'changeFreq' => $pagina->change_frequency ?? 'monthly',
                    ];
                });

            if ($total > 0) {
                return response()->json([
                    'data' => $records,
                    'total' => $total,
                ]);
            }

            $ultimoJob = $projeto->tarefasSitemap()->latest()->first();

            if (!$ultimoJob || empty($ultimoJob->artifacts)) {
                return response()->json(['data' => [], 'total' => 0]);
            }

            $artifacts = collect($ultimoJob->artifacts);
            $targetArtifact = $artifacts->firstWhere(fn($artifact) => isset($artifact['name']) && str_ends_with($artifact['name'], 'sitemap.xml'))
                ?? $artifacts->firstWhere(fn($artifact) => isset($artifact['name']) && str_ends_with($artifact['name'], '.txt'));

            if (!$targetArtifact) {
                return response()->json(['data' => [], 'total' => 0]);
            }

            $paths = [
                base_path('../api-sitemap/sitemaps/projects/' . $projeto->id . '/' . $targetArtifact['name']),
                base_path('../api-sitemap/sitemaps/' . $ultimoJob->external_job_id . '/' . $targetArtifact['name']),
            ];

            clearstatcache();
            $validPath = null;

            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $validPath = $path;
                    break;
                }
            }

            if (!$validPath) {
                return response()->json(['data' => [], 'total' => 0]);
            }

            $reader = new \App\Services\SitemapDataReaderService();
            $result = $reader->getPaginatedUrls($validPath, $page, $perPage, $search);

            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
