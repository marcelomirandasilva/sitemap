<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Models\TarefaSitemap;
use App\Models\Pagina;

use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RastreadorController extends Controller
{
    protected $sitemapService;

    public function __construct(SitemapGeneratorService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Inicia um novo processo de rastreamento para o projeto.
     */
    public function store(Request $request, Projeto $projeto)
    {
        // Autorização básica (idealmente usar Policies)
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            // Calcula o limite efetivo de páginas respeitando o plano do usuário
            $usuario = auth()->user()->load('plano');
            $limitePlano = $usuario->plano?->max_pages ?? 500; // Plano Free como fallback seguro
            $limiteEfetivo = min($projeto->max_pages ?? $limitePlano, $limitePlano);

            Log::info("RastreadorController: Iniciando job com limite de {$limiteEfetivo} páginas (plano: {$limitePlano}, projeto: {$projeto->max_pages})");

            // Validação de Segurança: Garante que o usuário não burlou o limite do plano para mídia
            $permiteImagens = (bool) ($usuario->plano?->permite_imagens);
            $permiteVideos = (bool) ($usuario->plano?->permite_videos);

            if ($projeto->check_images && !$permiteImagens) {
                $projeto->update(['check_images' => false]);
                Log::warning("RastreadorController: Rastreamento de imagens desativado (não permitido no plano)");
            }

            if ($projeto->check_videos && !$permiteVideos) {
                $projeto->update(['check_videos' => false]);
                Log::warning("RastreadorController: Rastreamento de vídeos desativado (não permitido no plano)");
            }

            $externalJobId = $this->sitemapService->startJob($projeto, $limiteEfetivo);

            if (!$externalJobId) {
                return response()->json(['message' => 'Falha ao iniciar o serviço de crawler. Tente novamente.'], 500);
            }

            // Registrar o Job localmente
            $job = $projeto->tarefasSitemap()->create([
                'external_job_id' => $externalJobId,
                'status' => 'queued',
                'started_at' => now(),
            ]);

            return response()->json([
                'message' => 'Rastreamento iniciado!',
                'job_id' => $externalJobId
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro no CrawlerController@store', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Ocorreu um erro interno ao iniciar o rastreamento. Tente novamente em instantes.'], 500);
        }
    }

    /**
     * Verifica e atualiza o status do último job do projeto.
     */
    public function getStatus(Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        $ultimo_job = $projeto->tarefasSitemap()->latest()->first();

        if (!$ultimo_job) {
            return response()->json([
                'status' => null,
                'progress' => 0,
                'message' => 'Nenhum rastreamento iniciado ainda.'
            ], 200);
        }


        // Consultar API externa para atualização somente se necessário
        // (Podemos otimizar futuramente para não chamar API se acabou de completar)
        $shouldCheckApi = !in_array($ultimo_job->status, ['completed', 'failed', 'cancelled']) || empty($ultimo_job->artifacts);

        $statusData = [];

        if ($shouldCheckApi) {
            Log::info("CrawlerController: Consultando API para Job {$ultimo_job->external_job_id}");
            $statusData = $this->sitemapService->checkStatus($ultimo_job->external_job_id, auth()->id());

            if ($statusData) {
                Log::info("CrawlerController: Resposta recebida da API", $statusData);

                $ultimo_job->update([
                    'status' => $statusData['status'] ?? $ultimo_job->status,
                    'progress' => $statusData['progress'] ?? $ultimo_job->progress,
                    'pages_count' => $statusData['result']['total_urls'] ?? $statusData['urls_found'] ?? $statusData['pages_count'] ?? $ultimo_job->pages_count ?? 0,
                    'images_count' => $statusData['result']['total_images'] ?? $statusData['images_found'] ?? 0,
                    'videos_count' => $statusData['result']['total_videos'] ?? $statusData['videos_found'] ?? 0,
                    'message' => $statusData['message'] ?? null,
                    'artifacts' => $statusData['artifacts'] ?? $ultimo_job->artifacts ?? [],
                ]);

                // IMPORTANTE: Recarregar o modelo para refletir as alterações do banco
                $ultimo_job->refresh();

                Log::info("CrawlerController: Job atualizado no BD", ['id' => $ultimo_job->id, 'status' => $ultimo_job->status, 'progress' => $ultimo_job->progress]);

                // Se completou agora e ainda não temos artifacts (fallback), buscar artefatos
                if ($ultimo_job->status === 'completed') {
                    if (empty($ultimo_job->artifacts)) {
                        Log::info("CrawlerController: Buscando artefatos explicitamente...");
                        $artifacts = $this->sitemapService->getArtifacts($ultimo_job->external_job_id, auth()->id());
                        $ultimo_job->update([
                            'artifacts' => $artifacts,
                        ]);
                        $ultimo_job->refresh();
                    }

                    // Atualizar status do Job e timestamp
                    $ultimo_job->update(['completed_at' => now()]);

                    // Atualizar o PROJETO pai
                    $projeto->update([
                        'last_crawled_at' => now(),
                        'status' => 'active' // Ativa o projeto se estava pendente
                    ]);

                    // IMPORTANTE: Dispara a ingestão de páginas para preencher a base de dados
                    \App\Jobs\ProcessSitemapArtifactsJob::dispatch($ultimo_job);
                }
            }
        }

        $previewUrls = [];
        if ($ultimo_job->status === 'completed') {
            $previewUrls = $this->sitemapService->getPreviewUrls($ultimo_job->artifacts ?? [], $ultimo_job->external_job_id);
            Log::info("CrawlerController: Gerado preview para Job {$ultimo_job->external_job_id}. Total URLs: " . count($previewUrls));
        }

        return response()->json([
            'status' => $ultimo_job->status,
            'progress' => $ultimo_job->progress,
            'message' => $ultimo_job->message ?? null,
            'pages_count' => $ultimo_job->pages_count,
            'urls_crawled' => $statusData['urls_crawled'] ?? $ultimo_job->pages_count ?? 0,
            'urls_found' => $statusData['urls_found'] ?? $ultimo_job->pages_count ?? 0,
            'images_count' => $ultimo_job->images_count ?? 0,
            'videos_count' => $ultimo_job->videos_count ?? 0,
            'artifacts' => $ultimo_job->artifacts,
            'preview_urls' => $previewUrls,
            'recent_pages' => $statusData['recent_pages'] ?? [],
            'current_url' => $statusData['current_url'] ?? null,
            'current_depth' => $statusData['current_depth'] ?? 0,
            'queue_size' => $statusData['queue_size'] ?? 0,
        ]);
    }

    /**
     * Retorna paginated URLs a partir do arquivo sitemap/txt
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

            // Lê diretamente da tabela pages (Pagina)
            $query = Pagina::where('project_id', $projeto->id);

            if ($search) {
                $query->where('url', 'like', '%' . $search . '%');
            }

            $total = $query->count();
            $records = $query->orderBy('id')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function ($p) {
                    return [
                        'url' => $p->url,
                        'title' => $p->title,
                        'status_code' => $p->status_code,
                        'content_type' => $p->content_type,
                        'size_bytes' => $p->size_bytes,
                        'lastMod' => $p->updated_at->toIso8601String(),
                        'priority' => $p->priority ?? '0.5',
                        'changeFreq' => $p->change_frequency ?? 'monthly',
                    ];
                });

            if ($total > 0) {
                return response()->json([
                    'data' => $records,
                    'total' => $total,
                ]);
            }

            // Fallback: se banco vazio, tenta ler do XML
            $ultimo_job = $projeto->tarefasSitemap()->latest()->first();

            if (!$ultimo_job || empty($ultimo_job->artifacts)) {
                return response()->json(['data' => [], 'total' => 0]);
            }

            $artifacts = collect($ultimo_job->artifacts);
            $targetArtifact = $artifacts->firstWhere(fn($a) => isset($a['name']) && str_ends_with($a['name'], 'sitemap.xml'))
                ?? $artifacts->firstWhere(fn($a) => isset($a['name']) && str_ends_with($a['name'], '.txt'));

            if (!$targetArtifact) {
                return response()->json(['data' => [], 'total' => 0]);
            }

            $paths = [
                base_path('../api-sitemap/sitemaps/projects/' . $projeto->id . '/' . $targetArtifact['name']),
                base_path('../api-sitemap/sitemaps/' . $ultimo_job->external_job_id . '/' . $targetArtifact['name']),
            ];

            clearstatcache();
            $validPath = null;
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $validPath = $p;
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
                'line' => $e->getLine()
            ], 500);
        }
    }
}
