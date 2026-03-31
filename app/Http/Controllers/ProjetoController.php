<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ProjetoController extends Controller
{
    protected $sitemapService;

    public function __construct(SitemapGeneratorService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    protected function normalizePlanFrequency(?string $frequency): string
    {
        $normalized = mb_strtolower(trim((string) $frequency));

        return match (true) {
            str_contains($normalized, 'manual') => 'manual',
            str_contains($normalized, 'di') => 'diario',
            str_contains($normalized, 'seman') => 'semanal',
            str_contains($normalized, 'quinzen') => 'quinzenal',
            str_contains($normalized, 'mens') => 'mensal',
            str_contains($normalized, 'anual') => 'anual',
            default => 'manual',
        };
    }

    protected function allowedFrequenciesForPlan(?string $planFrequency): array
    {
        $levels = [
            'manual' => 0,
            'anual' => 1,
            'mensal' => 2,
            'quinzenal' => 3,
            'semanal' => 4,
            'diario' => 5,
        ];

        $displayOrder = ['manual', 'diario', 'semanal', 'quinzenal', 'mensal', 'anual'];
        $normalizedPlanFrequency = $this->normalizePlanFrequency($planFrequency);
        $planLevel = $levels[$normalizedPlanFrequency] ?? 0;

        return array_values(array_filter($displayOrder, function ($value) use ($levels, $planLevel) {
            return ($levels[$value] ?? 0) <= $planLevel || $value === 'manual';
        }));
    }

    protected function buildProjectFeatures($usuario): array
    {
        $plano = $usuario->plano;

        return [
            'permite_imagens' => $plano ? (bool) $plano->permite_imagens : false,
            'permite_videos' => $plano ? (bool) $plano->permite_videos : false,
            'advanced_settings_enabled' => $plano ? (bool) $plano->has_advanced_features : false,
            'plan_max_pages' => $plano?->max_pages ?? 500,
            'plan_update_frequency' => $this->normalizePlanFrequency($plano?->update_frequency),
            'allowed_frequencies' => $this->allowedFrequenciesForPlan($plano?->update_frequency),
            'max_depth_limit' => 10,
            'max_concurrent_requests_limit' => 10,
            'delay_between_requests_min' => 0,
            'delay_between_requests_max' => 10,
        ];
    }

    protected function defaultPublishedSitemapUrl(?string $projectUrl): ?string
    {
        $host = parse_url((string) $projectUrl, PHP_URL_HOST);

        if (!$host) {
            return null;
        }

        $scheme = parse_url((string) $projectUrl, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url((string) $projectUrl, PHP_URL_PORT);
        $origin = $scheme . '://' . $host;

        if ($port) {
            $origin .= ':' . $port;
        }

        return $origin . '/sitemap.xml';
    }

    protected function maskBingApiKey(?string $apiKey): ?string
    {
        if (!$apiKey) {
            return null;
        }

        $visible = substr($apiKey, -6);

        return str_repeat('*', max(strlen($apiKey) - 6, 8)) . $visible;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url|active_url',
        ]);

        $parsed = parse_url($validated['url']);
        $name = $parsed['host'] ?? $validated['url'];

        $user = $request->user();
        $user->load('plano');

        $projeto = $user->projetos()->create([
            'name' => $name,
            'url' => $validated['url'],
            'status' => 'pending',
            'frequency' => 'manual',
            'check_images' => (bool) ($user->plano?->permite_imagens),
            'check_videos' => (bool) ($user->plano?->permite_videos),
        ]);

        $projeto->refresh();
        $limitePlano = $user->plano?->max_pages ?? 500;
        $limiteEfetivo = min($projeto->max_pages ?? $limitePlano, $limitePlano);

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
            Log::error('Erro ao iniciar crawler na criacao: ' . $e->getMessage());
        }

        return Redirect::back()->with('success', 'Website added successfully!');
    }

    public function show(Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        $jobHistory = $projeto->tarefasSitemap()
            ->latest()
            ->limit(10)
            ->get()
            ->values();

        $ultimoJob = $jobHistory->first();

        $usuario = auth()->user();
        $usuario->load('plano');
        $searchConnections = $usuario->searchEngineConnections()->get()->keyBy('provider');

        return Inertia::render('App/Projects/Show', [
            'projeto' => $projeto,
            'ultimo_job' => $ultimoJob,
            'job_history' => $jobHistory,
            'preview_urls' => [],
            'features' => $this->buildProjectFeatures($usuario),
            'search_engines' => [
                'suggested_sitemap_url' => $this->defaultPublishedSitemapUrl($projeto->url),
                'published_sitemap_url' => $projeto->published_sitemap_url,
                'google_site_property' => $projeto->google_site_property,
                'bing_site_url' => $projeto->bing_site_url,
                'connections' => [
                    'google' => [
                        'connected' => $searchConnections->has('google'),
                        'email' => $searchConnections->get('google')?->email,
                        'connected_at' => optional($searchConnections->get('google')?->connected_at)->toISOString(),
                    ],
                    'bing' => [
                        'connected' => $searchConnections->has('bing'),
                        'label' => $this->maskBingApiKey($searchConnections->get('bing')?->api_key),
                        'connected_at' => optional($searchConnections->get('bing')?->connected_at)->toISOString(),
                    ],
                ],
                'recent_submissions' => $projeto->searchEngineSubmissions()
                    ->latest('submitted_at')
                    ->limit(10)
                    ->get()
                    ->map(fn ($submission) => [
                        'id' => $submission->id,
                        'provider' => $submission->provider,
                        'site_identifier' => $submission->site_identifier,
                        'sitemap_url' => $submission->sitemap_url,
                        'status' => $submission->status,
                        'message' => $submission->message,
                        'submitted_at' => optional($submission->submitted_at)->toISOString(),
                    ])
                    ->values(),
            ],
        ]);
    }

    public function update(Request $request, Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        $usuario = auth()->user();
        $usuario->load('plano');
        $features = $this->buildProjectFeatures($usuario);
        $planMaxPages = $features['plan_max_pages'];
        $allowedFrequencies = $features['allowed_frequencies'];
        $advancedEnabled = $features['advanced_settings_enabled'];

        $validated = $request->validate([
            'check_images' => 'sometimes|boolean',
            'check_videos' => 'sometimes|boolean',
            'frequency' => 'sometimes|string|in:manual,diario,semanal,quinzenal,mensal,anual',
            'max_pages' => 'sometimes|integer|min:1',
            'max_depth' => 'sometimes|integer|min:1|max:10',
            'max_concurrent_requests' => 'sometimes|integer|min:1|max:10',
            'delay_between_requests' => 'sometimes|numeric|min:0|max:10',
            'user_agent_custom' => 'sometimes|nullable|string|max:255',
            'published_sitemap_url' => 'sometimes|nullable|url|max:2048',
            'google_site_property' => 'sometimes|nullable|string|max:255',
            'bing_site_url' => 'sometimes|nullable|url|max:2048',
        ]);

        if (isset($validated['check_images']) && $validated['check_images'] && !($usuario->plano?->permite_imagens)) {
            $validated['check_images'] = false;
        }

        if (isset($validated['check_videos']) && $validated['check_videos'] && !($usuario->plano?->permite_videos)) {
            $validated['check_videos'] = false;
        }

        if (isset($validated['frequency']) && !in_array($validated['frequency'], $allowedFrequencies, true)) {
            $validated['frequency'] = in_array($projeto->frequency, $allowedFrequencies, true)
                ? $projeto->frequency
                : ($features['plan_update_frequency'] ?? 'manual');
        }

        if (isset($validated['max_pages'])) {
            $validated['max_pages'] = min((int) $validated['max_pages'], (int) $planMaxPages);
        }

        if (!$advancedEnabled) {
            unset(
                $validated['max_depth'],
                $validated['max_concurrent_requests'],
                $validated['delay_between_requests'],
                $validated['user_agent_custom']
            );
        }

        if (array_key_exists('user_agent_custom', $validated)) {
            $validated['user_agent_custom'] = trim((string) ($validated['user_agent_custom'] ?? '')) ?: null;
        }

        if (array_key_exists('published_sitemap_url', $validated)) {
            $validated['published_sitemap_url'] = trim((string) ($validated['published_sitemap_url'] ?? '')) ?: null;
        }

        if (array_key_exists('google_site_property', $validated)) {
            $validated['google_site_property'] = trim((string) ($validated['google_site_property'] ?? '')) ?: null;
        }

        if (array_key_exists('bing_site_url', $validated)) {
            $validated['bing_site_url'] = trim((string) ($validated['bing_site_url'] ?? '')) ?: null;
        }

        $projeto->update($validated);

        return Redirect::back()->with('success', 'Configuracoes atualizadas!');
    }

    public function destroy(Projeto $projeto)
    {
        if ($projeto->user_id !== auth()->id()) {
            abort(403);
        }

        $projeto->delete();

        return Redirect::route('dashboard')->with('success', 'Projeto excluido com sucesso.');
    }
}
