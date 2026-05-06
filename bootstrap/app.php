<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$trustedProxies = env('TRUSTED_PROXIES');

if (is_string($trustedProxies)) {
    $trustedProxies = trim($trustedProxies);

    if ($trustedProxies === '' || $trustedProxies === '*') {
        $trustedProxies = null;
    } else {
        $trustedProxies = array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));
        $trustedProxies = $trustedProxies === [] ? null : $trustedProxies;
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        channels: __DIR__ . '/../routes/channels.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) use ($trustedProxies): void {
        $middleware->web(append: [
            \App\Http\Middleware\DefinirIdiomaAplicacao::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->trustProxies(at: $trustedProxies);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'idioma.publico' => \App\Http\Middleware\DefinirIdiomaPublico::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, $e, $request) {
            if ($response->getStatusCode() === 419) {
                return redirect()->route('login')->with([
                    'status' => 'Sua sessão expirou por inatividade. Por favor, faça login novamente.',
                ]);
            }

            return $response;
        });
    })->create();
