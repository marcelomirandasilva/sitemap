<?php

namespace App\Http\Controllers;

use App\Models\Projeto;
use App\Models\SearchEngineConnection;
use App\Models\SearchEngineSubmission;
use App\Services\CentralNotificacoesService;
use App\Services\BingWebmasterService;
use App\Services\GoogleSearchConsoleService;
use App\Support\ValidadorUrlExterna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectSearchEngineController extends Controller
{
    public function __construct(
        protected CentralNotificacoesService $centralNotificacoes,
        protected ValidadorUrlExterna $validadorUrlExterna,
    )
    {
    }

    public function googleSites(Request $request, Projeto $projeto, GoogleSearchConsoleService $googleSearchConsoleService): JsonResponse
    {
        $this->authorizeProject($request, $projeto);

        $connection = $this->connectionFor($request, 'google');
        try {
            $sites = $googleSearchConsoleService->listSites($connection);
            $recommended = $this->matchGoogleSiteProperty($projeto, $sites, $projeto->google_site_property);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'Nao foi possivel carregar as propriedades do Google Search Console.',
            ], 422);
        }

        return response()->json([
            'sites' => $sites,
            'recommended' => $recommended,
        ]);
    }

    public function bingSites(Request $request, Projeto $projeto, BingWebmasterService $bingWebmasterService): JsonResponse
    {
        $this->authorizeProject($request, $projeto);

        $connection = $this->connectionFor($request, 'bing');
        try {
            $sites = $bingWebmasterService->listSites((string) $connection->api_key);
            $recommended = $this->matchBingSite($projeto, $sites, $projeto->bing_site_url);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'Nao foi possivel carregar os sites do Bing Webmaster Tools.',
            ], 422);
        }

        return response()->json([
            'sites' => array_map(fn ($site) => ['site_url' => $site], $sites),
            'recommended' => $recommended,
        ]);
    }

    public function submitGoogle(Request $request, Projeto $projeto, GoogleSearchConsoleService $googleSearchConsoleService): JsonResponse
    {
        $this->authorizeProject($request, $projeto);

        $validated = $request->validate([
            'site_property' => 'required|string|max:255',
            'published_sitemap_url' => 'required|url|max:2048',
        ]);

        $this->validatePublishedSitemapUrlReachable($validated['published_sitemap_url']);
        $this->ensureGooglePropertyMatchesSitemap($validated['site_property'], $validated['published_sitemap_url']);

        $connection = $this->connectionFor($request, 'google');

        try {
            $result = $googleSearchConsoleService->submitSitemap(
                $connection,
                $validated['site_property'],
                $validated['published_sitemap_url']
            );

            $projeto->update([
                'published_sitemap_url' => $validated['published_sitemap_url'],
                'google_site_property' => $validated['site_property'],
            ]);

            $submission = $this->storeSubmission(
                $request,
                $projeto,
                'google',
                $validated['site_property'],
                $validated['published_sitemap_url'],
                'submitted',
                'Sitemap enviado ao Google Search Console.',
                $result
            );
            $this->centralNotificacoes->notificarEnvioBuscador($projeto->refresh()->load('user'), $submission);

            return response()->json([
                'success' => true,
                'message' => 'Sitemap enviado ao Google Search Console.',
                'submission' => $this->serializeSubmission($submission),
                'recent_submissions' => $this->serializeSubmissions($projeto),
                'published_sitemap_url' => $projeto->published_sitemap_url,
                'google_site_property' => $projeto->google_site_property,
            ]);
        } catch (\Throwable $exception) {
            $submission = $this->storeSubmission(
                $request,
                $projeto,
                'google',
                $validated['site_property'],
                $validated['published_sitemap_url'],
                'failed',
                $exception->getMessage(),
                ['error' => $exception->getMessage()]
            );
            $this->centralNotificacoes->notificarEnvioBuscador($projeto->refresh()->load('user'), $submission);

            return response()->json([
                'message' => $exception->getMessage(),
                'submission' => $this->serializeSubmission($submission),
                'recent_submissions' => $this->serializeSubmissions($projeto),
            ], 422);
        }
    }

    public function submitBing(Request $request, Projeto $projeto, BingWebmasterService $bingWebmasterService): JsonResponse
    {
        $this->authorizeProject($request, $projeto);

        $validated = $request->validate([
            'site_url' => 'required|url|max:2048',
            'published_sitemap_url' => 'required|url|max:2048',
        ]);

        $this->validatePublishedSitemapUrlReachable($validated['published_sitemap_url']);
        $this->ensureBingSiteMatchesSitemap($validated['site_url'], $validated['published_sitemap_url']);

        $connection = $this->connectionFor($request, 'bing');

        try {
            $result = $bingWebmasterService->submitSitemap(
                (string) $connection->api_key,
                $validated['site_url'],
                $validated['published_sitemap_url']
            );

            $projeto->update([
                'published_sitemap_url' => $validated['published_sitemap_url'],
                'bing_site_url' => $validated['site_url'],
            ]);

            $submission = $this->storeSubmission(
                $request,
                $projeto,
                'bing',
                $validated['site_url'],
                $validated['published_sitemap_url'],
                'submitted',
                'Sitemap enviado ao Bing Webmaster Tools.',
                $result
            );
            $this->centralNotificacoes->notificarEnvioBuscador($projeto->refresh()->load('user'), $submission);

            return response()->json([
                'success' => true,
                'message' => 'Sitemap enviado ao Bing Webmaster Tools.',
                'submission' => $this->serializeSubmission($submission),
                'recent_submissions' => $this->serializeSubmissions($projeto),
                'published_sitemap_url' => $projeto->published_sitemap_url,
                'bing_site_url' => $projeto->bing_site_url,
            ]);
        } catch (\Throwable $exception) {
            $submission = $this->storeSubmission(
                $request,
                $projeto,
                'bing',
                $validated['site_url'],
                $validated['published_sitemap_url'],
                'failed',
                $exception->getMessage(),
                ['error' => $exception->getMessage()]
            );
            $this->centralNotificacoes->notificarEnvioBuscador($projeto->refresh()->load('user'), $submission);

            return response()->json([
                'message' => $exception->getMessage(),
                'submission' => $this->serializeSubmission($submission),
                'recent_submissions' => $this->serializeSubmissions($projeto),
            ], 422);
        }
    }

    protected function authorizeProject(Request $request, Projeto $projeto): void
    {
        abort_unless($projeto->user_id === $request->user()->id, 403);
    }

    protected function connectionFor(Request $request, string $provider): SearchEngineConnection
    {
        return SearchEngineConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', $provider)
            ->firstOrFail();
    }

    protected function validatePublishedSitemapUrlReachable(string $sitemapUrl): void
    {
        $this->validadorUrlExterna->validarOuFalhar($sitemapUrl, 'published_sitemap_url');

        try {
            $urlAtual = $sitemapUrl;
            $tentativas = 0;
            $response = null;

            while ($tentativas < 3) {
                $response = Http::timeout(15)
                    ->withoutRedirecting()
                    ->accept('application/xml,text/xml,*/*')
                    ->head($urlAtual);

                if (in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                    $proximaUrl = $this->resolveRedirectLocation($urlAtual, $response->header('Location'));

                    if (!$proximaUrl) {
                        throw ValidationException::withMessages([
                            'published_sitemap_url' => 'A URL publica do sitemap respondeu com redirecionamento invalido.',
                        ]);
                    }

                    $this->validadorUrlExterna->validarOuFalhar($proximaUrl, 'published_sitemap_url');
                    $urlAtual = $proximaUrl;
                    $tentativas++;
                    continue;
                }

                break;
            }

            if (!$response) {
                throw ValidationException::withMessages([
                    'published_sitemap_url' => 'Nao foi possivel validar a URL publica do sitemap.',
                ]);
            }

            if (in_array($response->status(), [403, 405], true) || $response->failed()) {
                $response = Http::timeout(15)
                    ->withoutRedirecting()
                    ->accept('application/xml,text/xml,*/*')
                    ->get($urlAtual);
            }

            if ($response->failed()) {
                throw ValidationException::withMessages([
                    'published_sitemap_url' => 'A URL publica do sitemap nao respondeu com sucesso. Verifique se o arquivo ja esta acessivel no seu dominio.',
                ]);
            }
        } catch (\Throwable $exception) {
            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'published_sitemap_url' => 'Nao foi possivel acessar a URL publica do sitemap. Confirme o upload e tente novamente.',
            ]);
        }
    }

    protected function resolveRedirectLocation(string $baseUrl, ?string $location): ?string
    {
        if (!$location) {
            return null;
        }

        if (filter_var($location, FILTER_VALIDATE_URL)) {
            return $location;
        }

        $base = parse_url($baseUrl);

        if (!is_array($base) || empty($base['scheme']) || empty($base['host'])) {
            return null;
        }

        if (str_starts_with($location, '//')) {
            return $base['scheme'] . ':' . $location;
        }

        $host = $base['scheme'] . '://' . $base['host'];

        if (!empty($base['port'])) {
            $host .= ':' . $base['port'];
        }

        if (str_starts_with($location, '/')) {
            return $host . $location;
        }

        $pathBase = isset($base['path']) ? preg_replace('~/[^/]*$~', '/', $base['path']) : '/';

        return $host . $pathBase . $location;
    }

    protected function ensureGooglePropertyMatchesSitemap(string $siteProperty, string $sitemapUrl): void
    {
        $normalizedSitemapUrl = $this->normalizeComparableUrl($sitemapUrl);

        if (Str::startsWith($siteProperty, 'sc-domain:')) {
            $domain = Str::after($siteProperty, 'sc-domain:');
            $host = parse_url($normalizedSitemapUrl, PHP_URL_HOST) ?: '';

            if ($host === $domain || Str::endsWith($host, '.' . $domain)) {
                return;
            }
        } else {
            $normalizedProperty = $this->normalizeComparableUrl($siteProperty);

            if (Str::startsWith($normalizedSitemapUrl, $normalizedProperty)) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'site_property' => 'A propriedade selecionada no Google precisa corresponder ao dominio da URL publica do sitemap.',
        ]);
    }

    protected function ensureBingSiteMatchesSitemap(string $siteUrl, string $sitemapUrl): void
    {
        $normalizedSiteUrl = $this->normalizeComparableUrl($siteUrl);
        $normalizedSitemapUrl = $this->normalizeComparableUrl($sitemapUrl);

        if (Str::startsWith($normalizedSitemapUrl, $normalizedSiteUrl)) {
            return;
        }

        throw ValidationException::withMessages([
            'site_url' => 'O site selecionado no Bing precisa corresponder ao dominio da URL publica do sitemap.',
        ]);
    }

    protected function normalizeComparableUrl(?string $value): string
    {
        return rtrim((string) $value, '/') . '/';
    }

    protected function matchGoogleSiteProperty(Projeto $projeto, array $sites, ?string $storedProperty = null): ?string
    {
        $propertyValues = collect($sites)->pluck('site_url')->filter()->values();

        if ($storedProperty && $propertyValues->contains($storedProperty)) {
            return $storedProperty;
        }

        $projectOrigin = $this->projectOrigin($projeto->url);
        $projectHost = parse_url($projeto->url, PHP_URL_HOST) ?: '';

        $exact = $propertyValues->first(fn ($site) => $this->normalizeComparableUrl($site) === $this->normalizeComparableUrl($projectOrigin));
        if ($exact) {
            return $exact;
        }

        return $propertyValues->first(function ($site) use ($projectHost) {
            if (!Str::startsWith($site, 'sc-domain:')) {
                return false;
            }

            $domain = Str::after($site, 'sc-domain:');

            return $projectHost === $domain || Str::endsWith($projectHost, '.' . $domain);
        });
    }

    protected function matchBingSite(Projeto $projeto, array $sites, ?string $storedSite = null): ?string
    {
        $siteValues = collect($sites)->filter()->values();

        if ($storedSite && $siteValues->contains($storedSite)) {
            return $storedSite;
        }

        $projectOrigin = $this->projectOrigin($projeto->url);

        return $siteValues->first(fn ($site) => $this->normalizeComparableUrl($site) === $this->normalizeComparableUrl($projectOrigin));
    }

    protected function projectOrigin(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $port = parse_url($url, PHP_URL_PORT);
        $origin = $scheme . '://' . $host;

        if ($port) {
            $origin .= ':' . $port;
        }

        return $origin . '/';
    }

    protected function storeSubmission(
        Request $request,
        Projeto $projeto,
        string $provider,
        string $siteIdentifier,
        string $sitemapUrl,
        string $status,
        ?string $message,
        array $responsePayload = []
    ): SearchEngineSubmission {
        return SearchEngineSubmission::create([
            'user_id' => $request->user()->id,
            'project_id' => $projeto->id,
            'provider' => $provider,
            'site_identifier' => $siteIdentifier,
            'sitemap_url' => $sitemapUrl,
            'status' => $status,
            'message' => $message,
            'response_payload' => $responsePayload ?: null,
            'submitted_at' => now(),
        ]);
    }

    protected function serializeSubmissions(Projeto $projeto): array
    {
        return $projeto->searchEngineSubmissions()
            ->latest('submitted_at')
            ->limit(10)
            ->get()
            ->map(fn (SearchEngineSubmission $submission) => $this->serializeSubmission($submission))
            ->values()
            ->all();
    }

    protected function serializeSubmission(SearchEngineSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'provider' => $submission->provider,
            'site_identifier' => $submission->site_identifier,
            'sitemap_url' => $submission->sitemap_url,
            'status' => $submission->status,
            'message' => $submission->message,
            'submitted_at' => optional($submission->submitted_at)->toISOString(),
        ];
    }
}
