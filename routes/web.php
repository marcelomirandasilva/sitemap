<?php

use App\Http\Controllers\PerfilController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\Http\Controllers\ProjetoController;
use App\Http\Controllers\RastreadorController;
use App\Models\Projeto;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Public/LandingPage', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'plans' => \App\Models\Plano::all(), // Planos injetados
    ]);
});

Route::get('/about-sitemaps', fn() => redirect()->route('info.article', 'about-sitemaps'));
Route::get('/info/{slug}', [\App\Http\Controllers\ArtigoInfoController::class, 'show'])->name('info.article');

Route::get('/dashboard', [App\Http\Controllers\PainelController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Rotas exclusivas para usuários verificados (Core do App)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/projects', [ProjetoController::class, 'store'])->name('projects.store');
    Route::get('/projects/{projeto}', [ProjetoController::class, 'show'])->name('projects.show');
    Route::delete('/projects/{projeto}', [ProjetoController::class, 'destroy'])->name('projects.destroy');

    // Crawler (Ações no Projeto)
    Route::post('/projects/{projeto}/crawl', [RastreadorController::class, 'store'])->name('projects.crawl');
    Route::get('/projects/{projeto}/status', [RastreadorController::class, 'getStatus'])->name('projects.status');
    Route::get('/projects/{projeto}/preview', [RastreadorController::class, 'getPreviewUrls'])->name('projects.preview');
    Route::get('/projects/{projeto}/urls', [RastreadorController::class, 'getUrls'])->name('projects.urls');

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
    Route::put('/preferences/notifications', [App\Http\Controllers\PreferenciasController::class, 'updateNotifications'])->name('preferences.notifications.update');
    Route::put('/preferences/billing', [App\Http\Controllers\PreferenciasController::class, 'updateBilling'])->name('preferences.billing.update');
    Route::put('/preferences/password', [App\Http\Controllers\PreferenciasController::class, 'updatePassword'])->name('preferences.password.update');
    Route::get('/preferences', [App\Http\Controllers\PreferenciasController::class, 'edit'])->name('preferences.edit');
    Route::delete('/preferences/deactivate', [App\Http\Controllers\PreferenciasController::class, 'deactivate'])->name('preferences.deactivate');

    // Histórico de Pagamentos
    Route::get('/billing', [App\Http\Controllers\FaturamentoController::class, 'index'])->name('billing.index');
});

// Rotas de Desenvolvimento
Route::middleware(['auth'])->prefix('dev')->group(function () {
    Route::get('/api-test', [App\Http\Controllers\DevController::class, 'showApiTest'])->name('dev.api-test');
    Route::post('/api-test/run', [App\Http\Controllers\DevController::class, 'runApiTest'])->name('dev.api-test.run');
});

require __DIR__ . '/auth.php';
