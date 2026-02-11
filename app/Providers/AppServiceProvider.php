<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;


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

        // Listener para Webhooks do Cashier
        Event::listen(
            \Laravel\Cashier\Events\WebhookReceived::class,
            [\App\Listeners\StripeEventListener::class, 'handle']
        );


    }
}
