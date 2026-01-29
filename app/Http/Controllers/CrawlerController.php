<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SitemapJob;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CrawlerController extends Controller
{
    protected $sitemapService;

    public function __construct(SitemapGeneratorService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    /**
     * Inicia um novo processo de rastreamento para o projeto.
     */
    public function store(Request $request, Project $project)
    {
        // Autorização básica (idealmente usar Policies)
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $externalJobId = $this->sitemapService->startJob($project);

            if (!$externalJobId) {
                return response()->json(['message' => 'Falha ao iniciar o serviço de crawler. Tente novamente.'], 500);
            }

            // Registrar o Job localmente
            $job = $project->sitemapJobs()->create([
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
    public function show(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $latestJob = $project->sitemapJobs()->latest()->first();

        if (!$latestJob) {
            return response()->json([
                'status' => null,
                'progress' => 0,
                'message' => 'Nenhum rastreamento iniciado ainda.'
            ], 200);
        }


        // Consultar API externa para atualização somente se necessário
        // (Podemos otimizar futuramente para não chamar API se acabou de completar)
        $shouldCheckApi = !in_array($latestJob->status, ['completed', 'failed', 'cancelled']) || empty($latestJob->artifacts);

        if ($shouldCheckApi) {
            Log::info("CrawlerController: Consultando API para Job {$latestJob->external_job_id}");
            $statusData = $this->sitemapService->checkStatus($latestJob->external_job_id);

            if ($statusData) {
                Log::info("CrawlerController: Resposta recebida da API", [
                    'status' => $statusData['status'] ?? 'N/A',
                    'progress' => $statusData['progress'] ?? 'N/A'
                ]);

                $latestJob->update([
                    'status' => $statusData['status'] ?? $latestJob->status,
                    'progress' => $statusData['progress'] ?? $latestJob->progress,
                    'pages_count' => $statusData['result']['total_urls'] ?? $statusData['urls_found'] ?? $statusData['pages_count'] ?? $latestJob->pages_count ?? 0,
                    'images_count' => $statusData['result']['total_images'] ?? $statusData['images_found'] ?? 0,
                    'videos_count' => $statusData['result']['total_videos'] ?? $statusData['videos_found'] ?? 0,
                    'message' => $statusData['message'] ?? null,
                    'artifacts' => $statusData['artifacts'] ?? $latestJob->artifacts ?? [],
                ]);

                // IMPORTANTE: Recarregar o modelo para refletir as alterações do banco
                $latestJob->refresh();

                Log::info("CrawlerController: Job atualizado no BD", ['id' => $latestJob->id, 'status' => $latestJob->status, 'progress' => $latestJob->progress]);

                // Se completou agora e ainda não temos artifacts (fallback), buscar artefatos
                if ($latestJob->status === 'completed') {
                    if (empty($latestJob->artifacts)) {
                        Log::info("CrawlerController: Buscando artefatos explicitamente...");
                        $artifacts = $this->sitemapService->getArtifacts($latestJob->external_job_id);
                        $latestJob->update([
                            'artifacts' => $artifacts,
                        ]);
                        $latestJob->refresh();
                    }

                    // Atualizar status do Job e timestamp
                    $latestJob->update(['completed_at' => now()]);

                    // Atualizar o PROJETO pai
                    $project->update([
                        'last_crawled_at' => now(),
                        'status' => 'active' // Ativa o projeto se estava pendente
                    ]);
                }
            }
        }

        $previewUrls = [];
        if ($latestJob->status === 'completed') {
            $previewUrls = $this->sitemapService->getPreviewUrls($latestJob->artifacts ?? [], $latestJob->external_job_id);
            Log::info("CrawlerController: Gerado preview para Job {$latestJob->external_job_id}. Total URLs: " . count($previewUrls));
        }

        return response()->json([
            'status' => $latestJob->status,
            'progress' => $latestJob->progress,
            'pages_count' => $latestJob->pages_count,
            'images_count' => $latestJob->images_count ?? 0,
            'videos_count' => $latestJob->videos_count ?? 0,
            'artifacts' => $latestJob->artifacts,
            'preview_urls' => $previewUrls,
        ]);
    }

    /**
     * Retorna paginated URLs a partir do arquivo sitemap/txt
     */
    public function getUrls(Request $request, Project $project)
    {
        try {
            if ($project->user_id !== auth()->id()) {
                abort(403);
            }

            $latestJob = $project->sitemapJobs()->latest()->first();

            if (!$latestJob || empty($latestJob->artifacts)) {
                return response()->json([
                    'data' => [],
                    'total' => 0,
                    'message' => 'Nenhum artefato encontrado para leitura.'
                ]);
            }

            // Determinar qual arquivo ler (XML preferencia ou TXT)
            $artifacts = collect($latestJob->artifacts);

            $targetArtifact = $artifacts->firstWhere(function ($a) {
                return isset($a['name']) && str_ends_with($a['name'], 'sitemap.xml');
            }) ?? $artifacts->firstWhere(function ($a) {
                return isset($a['name']) && str_ends_with($a['name'], '.txt');
            });

            if (!$targetArtifact) {
                return response()->json(['data' => [], 'total' => 0]);
            }

            // Helper para resolver path (reaproveitando lógica do Service original ou duplicando simples)
            // Como o SitemapDataReaderService precisa de PATH, vamos resolver.
            // A estrutura de pastas deve bater com a do seu servidor.
            $paths = [
                base_path('../api-sitemap/sitemaps/projects/' . $project->id . '/' . $targetArtifact['name']),
                base_path('../api-sitemap/sitemaps/' . $latestJob->external_job_id . '/' . $targetArtifact['name']),
            ];

            $validPath = null;
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    $validPath = $p;
                    break;
                }
            }

            if (!$validPath) {
                // Debug: retornar paths tentados
                return response()->json([
                    'data' => [],
                    'total' => 0,
                    'error' => 'Arquivo físico não encontrado',
                    'debug_paths' => $paths
                ]);
            }

            // Instanciar leitor (Idealmente injetado, mas new aqui facilita por agora)
            $reader = new \App\Services\SitemapDataReaderService();

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 50);
            $search = $request->input('q');

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
