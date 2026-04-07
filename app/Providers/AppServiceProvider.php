<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isLocal()) {
            Event::listen(MigrationsEnded::class, function () {
                Artisan::call('schema:dump');
                echo "✔ Mapa do banco atualizado para a IA (schema:dump).\n";
            });
        }

        Vite::prefetch(concurrency: 3);

        RateLimiter::for('crawlers', function (Request $request) {
            $user = $request->user();

            if (!$user || $user->role === 'admin') {
                return Limit::none();
            }

            $plano = $user->planoEfetivo();
            $limit = 5; // Limite padrão para quem não tem plano

            if ($plano) {
                $limit = $plano->has_advanced_features ? 50 : 10;
            }

            return Limit::perMinute($limit)->by($user->id)->response(function () {
                return response()->json(['message' => 'Muitas requisições. Você expirou o limite de dispartos do seu plano.'], 429);
            });
        });

        // Listener para Webhooks do Cashier
        Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            [\App\Listeners\StripeEventListener::class, 'handle']
        );


    }
}
