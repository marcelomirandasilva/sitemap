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

        // Se o job já terminou, retornamos o status salvo sem consultar a API (performance)
        if (in_array($latestJob->status, ['completed', 'failed', 'cancelled'])) {
            return response()->json([
                'status' => $latestJob->status,
                'progress' => $latestJob->progress,
                'artifacts' => $latestJob->artifacts,
            ]);
        }

        // Consultar API externa para atualização
        $statusData = $this->sitemapService->checkStatus($latestJob->external_job_id);

        if ($statusData) {
            $latestJob->update([
                'status' => $statusData['status'] ?? $latestJob->status,
                'progress' => $statusData['progress'] ?? $latestJob->progress,
                'message' => $statusData['message'] ?? null,
            ]);

            // Se completou agora, buscar artefatos
            if ($latestJob->status === 'completed' && empty($latestJob->artifacts)) {
                $artifacts = $this->sitemapService->getArtifacts($latestJob->external_job_id);
                $latestJob->update([
                    'artifacts' => $artifacts,
                    'completed_at' => now(),
                ]);
            }
        }

        return response()->json([
            'status' => $latestJob->status,
            'progress' => $latestJob->progress,
            'artifacts' => $latestJob->artifacts,
        ]);
    }
}
