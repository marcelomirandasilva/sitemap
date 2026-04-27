<?php

use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PaginaPublicaController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\ProjectSearchEngineController;
use App\Http\Controllers\SearchEngineConnectionController;
use App\Http\Controllers\SeoSiteController;
use App\Notifications\EmailSistema;
use App\Notifications\RedefinirSenha;
use App\Notifications\SenhaAlterada;
use App\Notifications\WelcomeAndVerifyUser;
use App\Notifications\WelcomeNewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use App\Http\Controllers\ProjetoController;
use App\Http\Controllers\RastreadorController;
use App\Models\Projeto;

Route::get('/', [PaginaPublicaController::class, 'redirecionarRaiz'])->name('public.root');
Route::get('/changelog', [PaginaPublicaController::class, 'redirecionarChangelog'])->name('public.changelog.redirect');
Route::get('/status', [PaginaPublicaController::class, 'redirecionarStatus'])->name('public.status.redirect');
Route::get('/robots.txt', [SeoSiteController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoSiteController::class, 'sitemap'])->name('seo.sitemap');

Route::middleware('idioma.publico')->group(function () {
    Route::get('/{locale}', [PaginaPublicaController::class, 'landing'])
        ->whereIn('locale', ['pt', 'en'])
        ->name('public.landing');

    Route::get('/{locale}/changelog', [PaginaPublicaController::class, 'changelog'])
        ->whereIn('locale', ['pt', 'en'])
        ->name('public.changelog');

    Route::get('/{locale}/status', [PaginaPublicaController::class, 'status'])
        ->whereIn('locale', ['pt', 'en'])
        ->name('public.status');

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
    Route::get('/subscription/checkout/success', [App\Http\Controllers\AssinaturaController::class, 'sucessoCheckout'])->name('subscription.checkout.success');
    Route::get('/subscription/checkout/{priceId}', [App\Http\Controllers\AssinaturaController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/portal', [App\Http\Controllers\AssinaturaController::class, 'portal'])->name('portal');

    // Rotas de crawler movidas para o grupo verified acima
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
Route::middleware(['auth', 'admin'])->prefix('dev')->group(function () {
    Route::get('/api-test', [App\Http\Controllers\DevController::class, 'showApiTest'])->name('dev.api-test');
    Route::post('/api-test/run', [App\Http\Controllers\DevController::class, 'runApiTest'])->name('dev.api-test.run');
});

if (app()->environment(['local', 'testing'])) {
    Route::middleware(['auth'])->prefix('dev')->group(function () {
        Route::get('/email-test', function (Request $request) {
            $usuario = $request->user();
            $emailDestino = $usuario->email;
            $tokenAtivacao = Password::broker()->createToken($usuario);
            $tokenRedefinicao = Password::broker()->createToken($usuario);

            $notificacoes = [
                'ativacao_conta' => new WelcomeAndVerifyUser($tokenAtivacao),
                'boas_vindas' => new WelcomeNewUser('SenhaTemporaria#123'),
                'redefinicao_senha' => new RedefinirSenha($tokenRedefinicao),
                'senha_alterada' => new SenhaAlterada('127.0.0.1'),
                'rastreamento_concluido' => new EmailSistema([
                    'assunto' => 'Rastreamento concluido no GenMap',
                    'titulo' => 'Projeto atualizado',
                    'mensagem' => 'O rastreamento do projeto odia.ig.com.br foi concluido com sucesso.',
                    'linhas' => [
                        'Paginas encontradas: 500',
                        'Imagens indexadas: 457',
                        'Videos indexados: 0',
                    ],
                    'acao_texto' => 'Abrir projeto',
                    'url' => route('dashboard'),
                    'rodape' => 'Voce esta recebendo esta mensagem porque as notificacoes de projeto por e-mail estao ativas.',
                ]),
                'envio_buscadores' => new EmailSistema([
                    'assunto' => 'Envio aos buscadores concluido',
                    'titulo' => 'Sitemap enviado ao Google e Bing',
                    'mensagem' => 'O envio do sitemap foi registrado com sucesso nos buscadores conectados.',
                    'linhas' => [
                        'Projeto: odia.ig.com.br',
                        'Google Search Console: enviado',
                        'Bing Webmaster Tools: enviado',
                    ],
                    'acao_texto' => 'Ver historico',
                    'url' => route('dashboard'),
                ]),
                'suporte' => new EmailSistema([
                    'assunto' => 'Resposta do suporte GenMap',
                    'titulo' => 'Seu ticket recebeu uma resposta',
                    'mensagem' => 'Nossa equipe respondeu sua solicitacao e ja existe uma atualizacao disponivel.',
                    'linhas' => [
                        'Ticket: #1234',
                        'Assunto: Duvida sobre envio de sitemap',
                    ],
                    'acao_texto' => 'Abrir suporte',
                    'url' => route('support.index'),
                ]),
                'plano' => new EmailSistema([
                    'assunto' => 'Atualizacao no seu plano GenMap',
                    'titulo' => 'Situacao de assinatura alterada',
                    'mensagem' => 'Houve uma atualizacao relacionada ao seu plano e ao faturamento da conta.',
                    'linhas' => [
                        'Plano: Solo',
                        'Status: ativo',
                        'Proxima renovacao: 30/04/2026',
                    ],
                    'acao_texto' => 'Abrir faturamento',
                    'url' => route('billing.index'),
                ]),
            ];

            $tipoSolicitado = trim((string) $request->query('tipo', ''));

            if ($tipoSolicitado !== '') {
                abort_unless(array_key_exists($tipoSolicitado, $notificacoes), 422, 'Tipo de e-mail invalido.');
                $notificacoes = [
                    $tipoSolicitado => $notificacoes[$tipoSolicitado],
                ];
            }

            $resultados = [];
            $erros = [];

            foreach ($notificacoes as $chave => $notificacao) {
                try {
                    $usuario->notifyNow($notificacao);
                    $resultados[$chave] = 'enviado';
                } catch (\Throwable $erro) {
                    $resultados[$chave] = $erro->getMessage();
                    $erros[$chave] = $erro->getMessage();
                }
            }

            return response()->json([
                'ok' => empty($erros),
                'email_destino' => $emailDestino,
                'quantidade' => count($notificacoes),
                'tipos' => array_keys($notificacoes),
                'resultados' => $resultados,
                'erros' => $erros,
            ], empty($erros) ? 200 : 500);
        })->name('dev.email-test');
    });
}

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

        // Changelog
        Route::resource('changelog', \App\Http\Controllers\Admin\ChangelogController::class)->except(['show']);

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
