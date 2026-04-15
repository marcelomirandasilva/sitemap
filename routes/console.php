<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Cache::put('status_publico.agendador_ultima_execucao', now()->toISOString(), now()->addMinutes(10));
})->everyMinute()->name('status-publico-heartbeat');

Schedule::command('assinaturas:reconciliar-planos')->hourly();
Schedule::command('projetos:agendar-rastreamentos')->hourly();
