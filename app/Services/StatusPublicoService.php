<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class StatusPublicoService
{
    private const CHAVE_CACHE_API = 'status_publico.api_python';
    private const CHAVE_CACHE_AGENDADOR = 'status_publico.agendador_ultima_execucao';

    public function __construct(
        protected SitemapGeneratorService $sitemapGeneratorService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function montarStatus(): array
    {
        $componentes = [
            $this->statusAplicacaoWeb(),
            $this->statusApiPython(),
            $this->statusAgendador(),
        ];

        return [
            'status_geral' => $this->calcularStatusGeral($componentes),
            'atualizado_em' => now()->toISOString(),
            'componentes' => $componentes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function statusAplicacaoWeb(): array
    {
        return [
            'slug' => 'aplicacao_web',
            'nome' => 'Aplicacao web',
            'status' => 'operational',
            'mensagem' => 'Aplicacao Laravel respondendo normalmente.',
            'detalhes' => [
                'driver_sessao' => (string) config('session.driver'),
                'ambiente' => (string) config('app.env'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function statusApiPython(): array
    {
        $resultado = Cache::remember(self::CHAVE_CACHE_API, now()->addSeconds(30), function () {
            return $this->sitemapGeneratorService->testConnection();
        });

        $status = !empty($resultado['success']) ? 'operational' : 'outage';
        $mensagem = !empty($resultado['success'])
            ? 'API Python acessivel pelo Laravel.'
            : ($resultado['message'] ?? 'Falha na comunicacao com a API Python.');

        return [
            'slug' => 'api_python',
            'nome' => 'API Python',
            'status' => $status,
            'mensagem' => $mensagem,
            'detalhes' => [
                'base_url' => $resultado['base_url'] ?? null,
                'tempo_ms' => $resultado['duration_ms'] ?? null,
                'http_status' => $resultado['status'] ?? null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function statusAgendador(): array
    {
        $ultimaExecucao = Cache::get(self::CHAVE_CACHE_AGENDADOR);
        $ultimaExecucaoCarbon = $ultimaExecucao ? Carbon::parse($ultimaExecucao) : null;

        if (!$ultimaExecucaoCarbon) {
            return [
                'slug' => 'agendador',
                'nome' => 'Agendador',
                'status' => 'degraded',
                'mensagem' => 'Heartbeat do agendador ainda nao foi registrado.',
                'detalhes' => [
                    'ultima_execucao' => null,
                    'limite_esperado_minutos' => 5,
                ],
            ];
        }

        $minutosSemHeartbeat = $ultimaExecucaoCarbon->diffInMinutes(now());
        $status = $minutosSemHeartbeat <= 5 ? 'operational' : 'outage';
        $mensagem = $status === 'operational'
            ? 'Agendador registrando execucao recente.'
            : 'Agendador sem heartbeat recente. Verifique o schedule:work.';

        return [
            'slug' => 'agendador',
            'nome' => 'Agendador',
            'status' => $status,
            'mensagem' => $mensagem,
            'detalhes' => [
                'ultima_execucao' => $ultimaExecucaoCarbon->toISOString(),
                'minutos_sem_heartbeat' => $minutosSemHeartbeat,
                'limite_esperado_minutos' => 5,
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $componentes
     */
    protected function calcularStatusGeral(array $componentes): string
    {
        $status = collect($componentes)->pluck('status');

        if ($status->contains('outage')) {
            return 'outage';
        }

        if ($status->contains('degraded')) {
            return 'degraded';
        }

        return 'operational';
    }
}
