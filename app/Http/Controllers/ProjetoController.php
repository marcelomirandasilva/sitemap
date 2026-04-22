<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Services\ExecucaoRastreamentoService;
use App\Services\FrequenciaRastreamentoService;
use App\Services\RelatorioSeoBilingueService;
use App\Services\RelatorioSeoProjetoService;
use App\Services\SitemapGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProjetoController extends Controller
{
    protected $sitemapService;
    protected $relatorioSeoBilingue;
    protected $relatorioSeoProjeto;
    protected $frequenciaRastreamento;
    protected $execucaoRastreamento;

    public function __construct(
        SitemapGeneratorService $sitemapService,
        RelatorioSeoBilingueService $relatorioSeoBilingue,
        RelatorioSeoProjetoService $relatorioSeoProjeto,
        FrequenciaRastreamentoService $frequenciaRastreamento,
        ExecucaoRastreamentoService $execucaoRastreamento
    )
    {
        $this->sitemapService = $sitemapService;
        $this->relatorioSeoBilingue = $relatorioSeoBilingue;
        $this->relatorioSeoProjeto = $relatorioSeoProjeto;
        $this->frequenciaRastreamento = $frequenciaRastreamento;
        $this->execucaoRastreamento = $execucaoRastreamento;
    }

    protected function buildProjectFeatures($usuario): array
    {
        $plano = $usuario->planoEfetivo();
        $limitesIntervaloPersonalizado = $this->frequenciaRastreamento->limitesIntervaloPersonalizadoHoras();
        $limitePaginasApi = $this->sitemapService->limiteMaximoPaginasApi();
        $profundidadeMaximaLimite = $plano?->profundidadeMaximaLimiteEfetiva() ?? 3;
        $concorrenciaLimite = $plano?->concorrenciaLimiteEfetiva() ?? 2;
        $atrasoMinimo = $plano?->atrasoMinimoEfetivo() ?? 1.0;
        $atrasoMaximo = $plano?->atrasoMaximoEfetivo() ?? 1.0;
        $limitePaginasPlano = $plano?->max_pages ?? 500;
        $limitePaginasEfetivo = $plano?->limitePaginasEfetivo($limitePaginasApi) ?? min($limitePaginasPlano, $limitePaginasApi);

        return [
            'permite_imagens' => $plano ? (bool) $plano->permite_imagens : false,
            'permite_videos' => $plano ? (bool) $plano->permite_videos : false,
            'advanced_settings_enabled' => $plano ? (bool) $plano->has_advanced_features : false,
            'permite_noticias' => $plano ? (bool) ($plano->has_advanced_features && $plano->permite_noticias) : false,
            'permite_mobile' => $plano ? (bool) ($plano->has_advanced_features && $plano->permite_mobile) : false,
            'permite_compactacao' => $plano ? (bool) ($plano->has_advanced_features && $plano->permite_compactacao) : false,
            'permite_cache_crawler' => $plano ? (bool) ($plano->has_advanced_features && $plano->permite_cache_crawler) : false,
            'permite_padroes_exclusao' => $plano ? (bool) ($plano->has_advanced_features && $plano->permite_padroes_exclusao) : false,
            'permite_politicas_crawl' => $plano ? (bool) ($plano->has_advanced_features && $plano->permite_politicas_crawl) : false,
            'plan_max_pages' => $limitePaginasEfetivo,
            'api_max_pages_limit' => $limitePaginasApi,
            'commercial_plan_max_pages' => $limitePaginasPlano,
            'plan_update_frequency' => $this->frequenciaRastreamento->normalizarFrequencia($plano?->update_frequency),
            'allowed_frequencies' => $this->frequenciaRastreamento->frequenciasPermitidasParaPlano($plano?->update_frequency),
            'intervalo_personalizado_min_horas' => $limitesIntervaloPersonalizado['minimo'],
            'intervalo_personalizado_max_horas' => $limitesIntervaloPersonalizado['maximo'],
            'intervalo_personalizado_padrao_horas' => $plano?->intervaloPersonalizadoPadraoHorasEfetivo() ?? $limitesIntervaloPersonalizado['padrao'],
            'default_max_depth' => $plano?->profundidadeMaximaPadraoEfetiva() ?? 3,
            'max_depth_limit' => $profundidadeMaximaLimite,
            'default_max_concurrent_requests' => $plano?->concorrenciaPadraoEfetiva() ?? 2,
            'max_concurrent_requests_limit' => $concorrenciaLimite,
            'default_delay_between_requests' => $plano?->atrasoPadraoEfetivo() ?? 1.0,
            'delay_between_requests_min' => $atrasoMinimo,
            'delay_between_requests_max' => $atrasoMaximo,
            'supports_advanced_api_options' => $plano ? (bool) $plano->has_advanced_features : false,
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
        $planoEfetivo = $user->planoEfetivo();
        $frequenciaPadrao = $this->frequenciaRastreamento->normalizarFrequencia($planoEfetivo?->update_frequency);
        $intervaloPersonalizadoPadraoHoras = $planoEfetivo?->intervaloPersonalizadoPadraoHorasEfetivo()
            ?? $this->frequenciaRastreamento->limitesIntervaloPersonalizadoHoras()['padrao'];
        $limitePaginasApi = $this->sitemapService->limiteMaximoPaginasApi();
        $limitePaginasEfetivo = $planoEfetivo?->limitePaginasEfetivo($limitePaginasApi) ?? min(1000, $limitePaginasApi);
        $limiteProjetos = (int) ($planoEfetivo?->max_projects ?? 1);
        $quantidadeProjetos = $user->projetos()->count();

        if ($limiteProjetos !== -1 && $quantidadeProjetos >= $limiteProjetos) {
            throw ValidationException::withMessages([
                'url' => __('dashboard.project_limit_reached', [
                    'count' => $limiteProjetos,
                    'plan' => $planoEfetivo?->name ?? 'Free',
                ]),
            ]);
        }

        $projeto = $user->projetos()->create([
            'name' => $name,
            'url' => $validated['url'],
            'status' => 'pending',
            'frequency' => $frequenciaPadrao,
            'intervalo_personalizado_horas' => $frequenciaPadrao === 'customizado' ? $intervaloPersonalizadoPadraoHoras : null,
            'max_pages' => $limitePaginasEfetivo,
            'max_depth' => $planoEfetivo?->profundidadeMaximaPadraoEfetiva() ?? 3,
            'max_concurrent_requests' => $planoEfetivo?->concorrenciaPadraoEfetiva() ?? 2,
            'delay_between_requests' => $planoEfetivo?->atrasoPadraoEfetivo() ?? 1.0,
            'check_images' => (bool) ($planoEfetivo?->permite_imagens),
            'check_videos' => (bool) ($planoEfetivo?->permite_videos),
            'check_news' => false,
            'check_mobile' => false,
            'compress_output' => true,
            'enable_cache' => true,
        ]);

        $projeto->refresh();

        try {
            $resultado = $this->execucaoRastreamento->iniciar(
                $projeto,
                'Job criado e aguardando processamento.'
            );

            if (!$resultado['success']) {
                Log::warning("Falha ao iniciar crawler na criacao do projeto {$projeto->id}", $resultado);
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
        $planoEfetivo = $usuario->planoEfetivo();
        $projeto = $this->frequenciaRastreamento->sincronizarFrequenciaProjeto($projeto, $planoEfetivo);
        $projeto = $this->frequenciaRastreamento->garantirProximoRastreamento($projeto, $planoEfetivo);
        $searchConnections = $usuario->searchEngineConnections()->get()->keyBy('provider');
        $presetsPoliticaCrawl = $this->sitemapService->listCrawlPolicyPresets($usuario->id);
        $opcoesPoliticaCrawl = $this->sitemapService->getCrawlPolicyOptions($usuario->id);

        return Inertia::render('App/Projects/Show', [
            'projeto' => $projeto,
            'ultimo_job' => $ultimoJob,
            'job_history' => $jobHistory,
            'preview_urls' => [],
            'relatorio_seo' => $this->relatorioSeoProjeto->montarParaProjeto($projeto),
            'seo_bilingue' => $this->relatorioSeoBilingue->montarParaProjeto($projeto),
            'features' => $this->buildProjectFeatures($usuario),
            'politicas_crawl' => [
                'presets' => $presetsPoliticaCrawl,
                'options' => $opcoesPoliticaCrawl,
            ],
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
        $features = $this->buildProjectFeatures($usuario);
        $planoEfetivo = $usuario->planoEfetivo();
        $planMaxPages = $features['plan_max_pages'];
        $allowedFrequencies = $features['allowed_frequencies'];
        $advancedEnabled = $features['advanced_settings_enabled'];
        $intervaloPersonalizadoMinimo = $features['intervalo_personalizado_min_horas'] ?? 1;
        $intervaloPersonalizadoMaximo = $features['intervalo_personalizado_max_horas'] ?? 720;
        $profundidadeMaximaLimite = $features['max_depth_limit'] ?? 3;
        $concorrenciaMaximaLimite = $features['max_concurrent_requests_limit'] ?? 2;
        $atrasoMinimo = $features['delay_between_requests_min'] ?? 1;
        $atrasoMaximo = $features['delay_between_requests_max'] ?? 1;

        $validated = $request->validate([
            'check_images' => 'sometimes|boolean',
            'check_videos' => 'sometimes|boolean',
            'check_news' => 'sometimes|boolean',
            'check_mobile' => 'sometimes|boolean',
            'frequency' => 'sometimes|string|in:manual,diario,semanal,quinzenal,mensal,anual,customizado',
            'intervalo_personalizado_horas' => "sometimes|nullable|integer|min:{$intervaloPersonalizadoMinimo}|max:{$intervaloPersonalizadoMaximo}",
            'max_pages' => 'sometimes|integer|min:1',
            'max_depth' => "sometimes|integer|min:1|max:{$profundidadeMaximaLimite}",
            'max_concurrent_requests' => "sometimes|integer|min:1|max:{$concorrenciaMaximaLimite}",
            'delay_between_requests' => "sometimes|numeric|min:{$atrasoMinimo}|max:{$atrasoMaximo}",
            'user_agent_custom' => 'sometimes|nullable|string|max:255',
            'exclude_patterns' => 'sometimes|array',
            'exclude_patterns.*' => 'string|max:255',
            'crawl_policy_id' => 'sometimes|nullable|string|max:255',
            'compress_output' => 'sometimes|boolean',
            'enable_cache' => 'sometimes|boolean',
            'published_sitemap_url' => 'sometimes|nullable|url|max:2048',
            'google_site_property' => 'sometimes|nullable|string|max:255',
            'bing_site_url' => 'sometimes|nullable|url|max:2048',
            'notification_preferences' => 'sometimes|array',
            'notification_preferences.email_crawler_updates' => 'nullable|boolean',
            'notification_preferences.email_search_engine_updates' => 'nullable|boolean',
        ]);

        if (isset($validated['check_images']) && $validated['check_images'] && !($planoEfetivo?->permite_imagens)) {
            $validated['check_images'] = false;
        }

        if (isset($validated['check_videos']) && $validated['check_videos'] && !($planoEfetivo?->permite_videos)) {
            $validated['check_videos'] = false;
        }

        if (isset($validated['check_news']) && $validated['check_news'] && !($features['permite_noticias'] ?? false)) {
            $validated['check_news'] = false;
        }

        if (isset($validated['check_mobile']) && $validated['check_mobile'] && !($features['permite_mobile'] ?? false)) {
            $validated['check_mobile'] = false;
        }

        if (isset($validated['frequency']) && !in_array($validated['frequency'], $allowedFrequencies, true)) {
            $validated['frequency'] = in_array($projeto->frequency, $allowedFrequencies, true)
                ? $projeto->frequency
                : ($features['plan_update_frequency'] ?? 'manual');
        }

        $frequenciaSolicitada = $validated['frequency'] ?? $projeto->frequency;

        if ($frequenciaSolicitada === 'customizado') {
            $validated['intervalo_personalizado_horas'] = $this->frequenciaRastreamento->normalizarIntervaloPersonalizadoHoras(
                $validated['intervalo_personalizado_horas'] ?? $projeto->intervalo_personalizado_horas
            );
        } else {
            $validated['intervalo_personalizado_horas'] = null;
        }

        if (isset($validated['max_pages'])) {
            $validated['max_pages'] = $planMaxPages === -1
                ? (int) $validated['max_pages']
                : min((int) $validated['max_pages'], (int) $planMaxPages);
        }

        if (isset($validated['max_depth'])) {
            $validated['max_depth'] = min((int) $validated['max_depth'], (int) $profundidadeMaximaLimite);
        }

        if (isset($validated['max_concurrent_requests'])) {
            $validated['max_concurrent_requests'] = min((int) $validated['max_concurrent_requests'], (int) $concorrenciaMaximaLimite);
        }

        if (isset($validated['delay_between_requests'])) {
            $validated['delay_between_requests'] = max((float) $atrasoMinimo, min((float) $validated['delay_between_requests'], (float) $atrasoMaximo));
        }

        if (!$advancedEnabled) {
            unset(
                $validated['max_depth'],
                $validated['max_concurrent_requests'],
                $validated['delay_between_requests'],
                $validated['user_agent_custom'],
                $validated['check_news'],
                $validated['check_mobile'],
                $validated['exclude_patterns'],
                $validated['crawl_policy_id'],
                $validated['compress_output'],
                $validated['enable_cache']
            );
        }

        if (!($features['permite_padroes_exclusao'] ?? false)) {
            unset($validated['exclude_patterns']);
        }

        if (!($features['permite_politicas_crawl'] ?? false)) {
            unset($validated['crawl_policy_id']);
        }

        if (!($features['permite_compactacao'] ?? false)) {
            unset($validated['compress_output']);
        }

        if (!($features['permite_cache_crawler'] ?? false)) {
            unset($validated['enable_cache']);
        }

        if (array_key_exists('user_agent_custom', $validated)) {
            $validated['user_agent_custom'] = trim((string) ($validated['user_agent_custom'] ?? '')) ?: null;
        }

        if (array_key_exists('exclude_patterns', $validated)) {
            $validated['exclude_patterns'] = collect($validated['exclude_patterns'] ?? [])
                ->map(fn ($padrao) => trim((string) $padrao))
                ->filter()
                ->values()
                ->all();
        }

        if (array_key_exists('crawl_policy_id', $validated)) {
            $validated['crawl_policy_id'] = trim((string) ($validated['crawl_policy_id'] ?? '')) ?: null;
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

        if (array_key_exists('notification_preferences', $validated)) {
            $validated['notification_preferences'] = array_merge(
                $projeto->notification_preferences ?? [],
                $validated['notification_preferences'] ?? []
            );
        }

        $frequenciaAlterada = array_key_exists('frequency', $validated);
        $intervaloPersonalizadoAlterado = array_key_exists('intervalo_personalizado_horas', $validated)
            && $projeto->intervalo_personalizado_horas !== $validated['intervalo_personalizado_horas'];

        $projeto->update($validated);
        $projeto->refresh();

        if ($frequenciaAlterada || $intervaloPersonalizadoAlterado) {
            $this->frequenciaRastreamento->atualizarProximoRastreamento($projeto, null, $planoEfetivo);
        } else {
            $this->frequenciaRastreamento->garantirProximoRastreamento($projeto, $planoEfetivo);
        }

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
