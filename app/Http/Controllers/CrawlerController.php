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
                return back()->with('error', 'Falha ao iniciar o serviço de crawler. Tente novamente.');
            }

            // Registrar o Job localmente
            $job = $project->sitemapJobs()->create([
                'external_job_id' => $externalJobId,
                'status' => 'queued',
                'started_at' => now(),
            ]);

            return back()->with('success', 'Rastreamento iniciado! ID: ' . $externalJobId);

        } catch (\Exception $e) {
            Log::error('Erro no CrawlerController@store', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erro interno ao iniciar rastreamento: ' . $e->getMessage());
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
            return response()->json(['message' => 'Nenhum job encontrado'], 404);
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

            Log::info("CrawlerController: Job atualizado no BD", ['id' => $latestJob->id, 'status' => $latestJob->status]);

            // Se completou agora e ainda não temos artifacts (fallback), buscar artefatos
            if ($latestJob->status === 'completed' && empty($latestJob->artifacts)) {
                Log::info("CrawlerController: Buscando artefatos explicitamente...");
                $artifacts = $this->sitemapService->getArtifacts($latestJob->external_job_id);
                $latestJob->update([
                    'artifacts' => $artifacts,
                    'completed_at' => now(),
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
            'artifacts' => $latestJob->artifacts,
            'preview_urls' => $previewUrls,
        ]);
    }
}
