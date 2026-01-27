<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProjectController extends Controller
{
    protected $sitemapService;

    public function __construct(SitemapGeneratorService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }
    public function store(Request $request)
    {
        // 1. Valida
        $validated = $request->validate([
            'url' => 'required|url|active_url',
        ]);

        // 2. Extrai o nome do site (ex: google.com)
        $parsed = parse_url($validated['url']);
        $name = $parsed['host'] ?? $validated['url'];

        // 3. Verifica limites do plano (OPCIONAL - Faremos depois)
        // ...

        // 4. Cria o projeto
        $project = $request->user()->projects()->create([
            'name' => $name,
            'url' => $validated['url'],
            'status' => 'pending',
            'frequency' => 'weekly',
        ]);

        // 5. Inicia o Crawler Automaticamente
        try {
            $externalJobId = $this->sitemapService->startJob($project);

            if ($externalJobId) {
                $project->sitemapJobs()->create([
                    'external_job_id' => $externalJobId,
                    'status' => 'queued',
                    'started_at' => now(),
                ]);
            } else {
                Log::warning("Falha ao iniciar crawler no startJob para o projeto {$project->id}");
            }

        } catch (\Exception $e) {
            // Não falha a criação do projeto, mas loga o erro
            Log::error('Erro ao iniciar crawler na criação: ' . $e->getMessage());
        }

        return Redirect::back()->with('success', 'Website added successfully!');
    }

    public function show(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        $project->load([
            'sitemapJobs' => function ($query) {
                $query->latest()->limit(1);
            }
        ]);

        $latestJob = $project->sitemapJobs->first();

        // Auto-recovery: Se status é completed mas não tem dados, tenta buscar novamente
        if ($latestJob && $latestJob->status === 'completed' && (empty($latestJob->artifacts) || $latestJob->pages_count === 0)) {
            try {
                $statusData = $this->sitemapService->checkStatus($latestJob->external_job_id);

                if ($statusData) {
                    $latestJob->update([
                        'pages_count' => $statusData['result']['total_urls'] ?? $statusData['pages_count'] ?? $latestJob->pages_count,
                        // Se a API retornar artifacts no checkStatus, usamos.
                        'artifacts' => $statusData['artifacts'] ?? $latestJob->artifacts ?? [],
                    ]);

                    // Se artifacts veio vazio do checkStatus, tenta buscar explicitamente
                    if (empty($statusData['artifacts'])) {
                        $artifacts = $this->sitemapService->getArtifacts($latestJob->external_job_id);
                        if (!empty($artifacts)) {
                            $latestJob->update(['artifacts' => $artifacts]);
                        }
                    }
                    $latestJob->refresh();
                }
            } catch (\Exception $e) {
                Log::error("Erro no auto-recovery do ProjectController: " . $e->getMessage());
            }
        }

        $previewUrls = [];

        // Se o job estiver completo, tentamos buscar o preview real
        if ($latestJob && $latestJob->status === 'completed' && !empty($latestJob->artifacts)) {
            $previewUrls = $this->sitemapService->getPreviewUrls($latestJob->artifacts);
        }

        return Inertia::render('App/Projects/Show', [
            'project' => $project,
            'latest_job' => $latestJob,
            'preview_urls' => $previewUrls
        ]);
    }
}