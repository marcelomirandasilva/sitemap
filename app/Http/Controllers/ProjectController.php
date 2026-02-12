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

        // 4. Verifica se usuário tem plano PRO (com features avançadas)
        $user = $request->user();
        $user->load('plan');
        $temRecursosAvancados = $user->plan && $user->plan->has_advanced_features;

        // 5. Cria o projeto com configurações baseadas no plano
        $project = $user->projects()->create([
            'name' => $name,
            'url' => $validated['url'],
            'status' => 'pending',
            'frequency' => 'manual',
            'check_images' => $temRecursosAvancados,
            'check_videos' => $temRecursosAvancados,
        ]);

        // 6. Inicia o Crawler Automaticamente
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

        // Carrega apenas o último job do banco de dados (rápido)
        $project->load([
            'sitemapJobs' => function ($query) {
                $query->latest()->limit(1);
            }
        ]);

        $latestJob = $project->sitemapJobs->first();

        // Removido auto-recovery síncrono e getPreviewUrls síncrono para evitar bloquear o render.
        // O frontend (Show.vue) agora faz uma chamada AJAX para buscar esses dados assim que monta via CrawlerController.

        // Eager load do plano para verificar permissões
        $user = auth()->user();
        $user->load('plan');

        $features = [
            'images_videos' => $user->plan ? (bool) $user->plan->has_advanced_features : false,
        ];

        return Inertia::render('App/Projects/Show', [
            'project' => $project,
            'latest_job' => $latestJob,
            'preview_urls' => [], // Frontend busca via AJAX
            'features' => $features
        ]);
    }

    public function destroy(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(403);
        }

        // Excluir jobs e arquivos relacionados (Se 'cascade' estiver no banco, o DB faz isso. 
        // Caso contrário, precisaríamos limpar manualmente os arquivos artifacts)
        // Por segurança, vamos apagar o registro e assumir que relationships cuidam do resto OU que Jobs serão órfãos.
        // O ideal é ter foreign keys com ON DELETE CASCADE.

        $project->delete();

        return Redirect::route('dashboard')->with('success', 'Projeto excluído com sucesso.');
    }
}
