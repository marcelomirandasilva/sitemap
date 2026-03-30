<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ProjetoController extends Controller
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
        $user->load('plano');
        $temRecursosAvancados = $user->plano && $user->plano->has_advanced_features;

        // 5. Cria o projeto com configurações baseadas no plano
        $projeto = $user->projetos()->create([
            'name' => $name,
            'url' => $validated['url'],
            'status' => 'pending',
            'frequency' => 'manual',
            'check_images' => (bool) ($user->plano?->permite_imagens),
            'check_videos' => (bool) ($user->plano?->permite_videos),
        ]);

        // Recarrega defaults do banco para iniciar o job com a mesma configuração
        // usada pelo fluxo de "retomar / iniciar".
        $projeto->refresh();
        $limitePlano = $user->plano?->max_pages ?? 500;
        $limiteEfetivo = min($projeto->max_pages ?? $limitePlano, $limitePlano);

        // 6. Inicia o Crawler Automaticamente
        try {
            $externalJobId = $this->sitemapService->startJob($projeto, $limiteEfetivo);

            if ($externalJobId) {
                $projeto->tarefasSitemap()->create([
                    'external_job_id' => $externalJobId,
                    'status' => 'queued',
                    'started_at' => now(),
                ]);
            } else {
                Log::warning("Falha ao iniciar crawler no startJob para o projeto {$projeto->id}");
            }

        } catch (\Exception $e) {
            // Não falha a criação do projeto, mas loga o erro
            Log::error('Erro ao iniciar crawler na criação: ' . $e->getMessage());
        }

        return Redirect::back()->with('success', 'Website added successfully!');
    }

    public function show(Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        // Carrega apenas o último job do banco de dados (rápido)
        $projeto->load([
            'tarefasSitemap' => function ($query) {
                $query->latest()->limit(1);
            }
        ]);

        $ultimo_job = $projeto->tarefasSitemap->first();

        // Eager load do plano para verificar permissões
        $usuario = auth()->user();
        $usuario->load('plano');

        $funcionalidades = [
            'permite_imagens' => $usuario->plano ? (bool) $usuario->plano->permite_imagens : false,
            'permite_videos' => $usuario->plano ? (bool) $usuario->plano->permite_videos : false,
        ];

        return Inertia::render('App/Projects/Show', [
            'projeto' => $projeto,
            'ultimo_job' => $ultimo_job,
            'preview_urls' => [], // Frontend busca via AJAX
            'features' => $funcionalidades
        ]);
    }

    public function update(Request $request, Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        $usuario = auth()->user();
        $usuario->load('plano');

        $validated = $request->validate([
            'check_images' => 'boolean',
            'check_videos' => 'boolean',
        ]);

        // Validação extra: não permitir ativar se o plano não permitir
        if (isset($validated['check_images']) && $validated['check_images'] && !($usuario->plano?->permite_imagens)) {
            $validated['check_images'] = false;
        }

        if (isset($validated['check_videos']) && $validated['check_videos'] && !($usuario->plano?->permite_videos)) {
            $validated['check_videos'] = false;
        }

        $projeto->update($validated);

        return Redirect::back()->with('success', 'Configurações atualizadas!');
    }

    public function destroy(Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        $projeto->delete();

        return Redirect::route('dashboard')->with('success', 'Projeto excluído com sucesso.');
    }
}
