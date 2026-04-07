<?php

use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PaginaPublicaController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\ProjectSearchEngineController;
use App\Http\Controllers\SearchEngineConnectionController;
use App\Http\Controllers\SeoSiteController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\Http\Controllers\ProjetoController;
use App\Http\Controllers\RastreadorController;
use App\Models\Projeto;

Route::get('/', [PaginaPublicaController::class, 'redirecionarRaiz'])->name('public.root');
Route::get('/robots.txt', [SeoSiteController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoSiteController::class, 'sitemap'])->name('seo.sitemap');

Route::middleware('idioma.publico')->group(function () {
    Route::get('/{locale}', [PaginaPublicaController::class, 'landing'])
        ->whereIn('locale', ['pt', 'en'])
        ->name('public.landing');

    Route::get('/{locale}/info/{slug}', [\App\Http\Controllers\ArtigoInfoController::class, 'show'])
        ->whereIn('locale', ['pt', 'en'])
        ->name('info.article');
});

Route::get('/about-sitemaps', fn() => redirect()->route('info.article', [
    'locale' => config('app.locale', 'pt'),
    'slug' => 'about-sitemaps',
]));
Route::get('/info/{slug}', fn(string $slug) => redirect()->route('info.article', [
    'locale' => config('app.locale', 'pt'),
    'slug' => $slug,
]));

Route::get('/dashboard', [App\Http\Controllers\PainelController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Rotas exclusivas para usuários verificados (Core do App)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/projects', [ProjetoController::class, 'store'])->name('projects.store');
    Route::get('/projects/{projeto}', [ProjetoController::class, 'show'])->name('projects.show');
    Route::patch('/projects/{projeto}', [ProjetoController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{projeto}', [ProjetoController::class, 'destroy'])->name('projects.destroy');

    // Crawler (Ações no Projeto)
    Route::post('/projects/{projeto}/crawl', [RastreadorController::class, 'store'])
        ->middleware('throttle:crawlers')
        ->name('projects.crawl');
    Route::post('/projects/{projeto}/crawl/cancel', [RastreadorController::class, 'cancel'])
        ->middleware('throttle:crawlers')
        ->name('projects.crawl.cancel');
    Route::get('/projects/{projeto}/status', [RastreadorController::class, 'getStatus'])->name('projects.status');
    Route::get('/projects/{projeto}/preview', [RastreadorController::class, 'getPreviewUrls'])->name('projects.preview');
    Route::get('/projects/{projeto}/urls', [RastreadorController::class, 'getUrls'])->name('projects.urls');
    Route::get('/projects/{projeto}/search-engines/google/sites', [ProjectSearchEngineController::class, 'googleSites'])->name('projects.search-engines.google.sites');
    Route::get('/projects/{projeto}/search-engines/bing/sites', [ProjectSearchEngineController::class, 'bingSites'])->name('projects.search-engines.bing.sites');
    Route::post('/projects/{projeto}/search-engines/google/submit', [ProjectSearchEngineController::class, 'submitGoogle'])->name('projects.search-engines.google.submit');
    Route::post('/projects/{projeto}/search-engines/bing/submit', [ProjectSearchEngineController::class, 'submitBing'])->name('projects.search-engines.bing.submit');

    // Rota de Download via Proxy
    Route::get('/downloads/{jobId}/{filename}', [\App\Http\Controllers\DownloadController::class, 'sitemap'])->name('downloads.sitemap');

    // Gestão de Chaves de API (acesso externo à API)
    Route::prefix('settings/api-keys')->name('api-keys.')->controller(\App\Http\Controllers\ChaveApiController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::patch('/{chaveApi}/revoke', 'revoke')->name('revoke');
        Route::delete('/{chaveApi}', 'destroy')->name('destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [PerfilController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [PerfilController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [PerfilController::class, 'destroy'])->name('profile.destroy');
});

Route::get('auth/google', [App\Http\Controllers\Auth\SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [App\Http\Controllers\Auth\SocialAuthController::class, 'handleGoogleCallback']);

// Webhook do Stripe (Explicitamente definido para evitar 404)
Route::post('/stripe/webhook', [\Laravel\Cashier\Http\Controllers\WebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Webhook interno da API Python (notificação de conclusão de job)
// Sem CSRF nem auth de sessão — autenticação via X-Internal-Token no controller
Route::post('/api/internal/webhook/job-completed', [\App\Http\Controllers\WebhookSitemapController::class, 'jobCompleted'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.job-completed');

Route::middleware(['auth'])->group(function () {
    Route::get('/subscription', [App\Http\Controllers\AssinaturaController::class, 'index'])->name('subscription.index');
    Route::get('/subscription/checkout/{priceId}', [App\Http\Controllers\AssinaturaController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/portal', [App\Http\Controllers\AssinaturaController::class, 'portal'])->name('portal');

    // Rotas de Crawler movidas para o grupo verified acima
    // Assinaturas (Cashier)
    Route::get('/subscription', [App\Http\Controllers\AssinaturaController::class, 'index'])->name('subscription.index');
    Route::get('/subscription/checkout/{priceId}', [App\Http\Controllers\AssinaturaController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/billing-portal', [App\Http\Controllers\AssinaturaController::class, 'portal'])->name('subscription.portal');

    // Preferências do Usuário
    Route::put('/preferences/ui', [App\Http\Controllers\PreferenciasController::class, 'updateUi'])->name('preferences.ui.update');
    Route::put('/preferences/locale', [App\Http\Controllers\PreferenciasController::class, 'updateLocale'])->name('preferences.locale.update');
    Route::put('/preferences/notifications', [App\Http\Controllers\PreferenciasController::class, 'updateNotifications'])->name('preferences.notifications.update');
    Route::put('/preferences/billing', [App\Http\Controllers\PreferenciasController::class, 'updateBilling'])->name('preferences.billing.update');
    Route::put('/preferences/password', [App\Http\Controllers\PreferenciasController::class, 'updatePassword'])->name('preferences.password.update');
    Route::get('/preferences', [App\Http\Controllers\PreferenciasController::class, 'edit'])->name('preferences.edit');
    Route::delete('/preferences/deactivate', [App\Http\Controllers\PreferenciasController::class, 'deactivate'])->name('preferences.deactivate');
    Route::get('/notifications', [NotificacaoController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificacaoController::class, 'marcarTodasComoLidas'])->name('notifications.read-all');
    Route::post('/notifications/{notificacaoId}/read', [NotificacaoController::class, 'marcarComoLida'])->name('notifications.read');
    Route::get('/integrations/google/search-console/connect', [SearchEngineConnectionController::class, 'redirectToGoogle'])->name('search-engines.google.connect');
    Route::get('/integrations/google/search-console/callback', [SearchEngineConnectionController::class, 'handleGoogleCallback'])->name('search-engines.google.callback');
    Route::delete('/integrations/google/search-console', [SearchEngineConnectionController::class, 'disconnectGoogle'])->name('search-engines.google.disconnect');
    Route::post('/integrations/bing/webmaster', [SearchEngineConnectionController::class, 'storeBingKey'])->name('search-engines.bing.store');
    Route::delete('/integrations/bing/webmaster', [SearchEngineConnectionController::class, 'destroyBingKey'])->name('search-engines.bing.destroy');

    // Histórico de Pagamentos
    Route::get('/billing', [App\Http\Controllers\FaturamentoController::class, 'index'])->name('billing.index');

    // Página de API do Usuário (Referência + Setup)
    Route::get('/account/api', [App\Http\Controllers\ApiController::class, 'index'])->name('account.api');
    Route::post('/account/api/reset-key', [App\Http\Controllers\ApiController::class, 'resetKey'])->name('account.api.reset-key');
    Route::post('/account/api/callback-url', [App\Http\Controllers\ApiController::class, 'saveCallbackUrl'])->name('account.api.callback-url');

    // Sistema de Suporte ao Usuário (Tickets)
    Route::prefix('support')->name('support.')->controller(App\Http\Controllers\TicketController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{ticket}', 'show')->name('show');
        Route::post('/{ticket}/reply', 'reply')->name('reply');
        Route::patch('/{ticket}/fechar', 'fechar')->name('fechar');
    });
});

// Rotas de Desenvolvimento
Route::middleware(['auth'])->prefix('dev')->group(function () {
    Route::get('/api-test', [App\Http\Controllers\DevController::class, 'showApiTest'])->name('dev.api-test');
    Route::post('/api-test/run', [App\Http\Controllers\DevController::class, 'runApiTest'])->name('dev.api-test.run');
});

require __DIR__ . '/auth.php';

// Área de Gestão (Back-office Nativo Vue + Inertia)
Route::prefix('gestao')->name('admin.')->group(function () {

    // Auth Administrativo Independente
    Route::middleware('guest')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'store']);
    });

    // Rotas Protegidas da Gestão
    Route::middleware(['auth', 'verified', 'admin'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PainelController::class, 'index'])->name('dashboard');
        Route::post('/logout', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');

        // Usuários
        Route::resource('users', \App\Http\Controllers\Admin\UsuarioController::class)->except(['create', 'store', 'show', 'destroy']);
        Route::post('users/{user}/impersonate', [\App\Http\Controllers\Admin\UsuarioController::class, 'impersonate'])->name('users.impersonate');

        // Planos
        Route::resource('plans', \App\Http\Controllers\Admin\PlanoController::class)->except(['show']);

        // Tickets
        Route::get('tickets', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [\App\Http\Controllers\Admin\TicketController::class, 'reply'])->name('tickets.reply');
        Route::put('tickets/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'update'])->name('tickets.update');

        // Monitoramento Crawler
        Route::get('jobs', [\App\Http\Controllers\Admin\TarefaSitemapController::class, 'index'])->name('jobs.index');
        Route::get('jobs/{job}', [\App\Http\Controllers\Admin\TarefaSitemapController::class, 'show'])->name('jobs.show');
        Route::delete('jobs/{job}/cancel', [\App\Http\Controllers\Admin\TarefaSitemapController::class, 'cancel'])->name('jobs.cancel');
    });
});
