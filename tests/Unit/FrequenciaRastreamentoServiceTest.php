<?php

namespace Tests\Unit;

use App\Models\Plano;
use App\Models\Projeto;
use App\Services\FrequenciaRastreamentoService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FrequenciaRastreamentoServiceTest extends TestCase
{
    public function test_plano_customizado_libera_todas_as_frequencias_inclusive_personalizada(): void
    {
        $service = new FrequenciaRastreamentoService();

        $frequencias = $service->frequenciasPermitidasParaPlano('customizado');

        $this->assertSame(
            ['manual', 'diario', 'semanal', 'quinzenal', 'mensal', 'anual', 'customizado'],
            $frequencias
        );
    }

    public function test_calcula_proximo_rastreamento_com_intervalo_personalizado_em_horas(): void
    {
        $service = new FrequenciaRastreamentoService();
        $referencia = Carbon::parse('2026-04-14 10:00:00');

        $projeto = new Projeto([
            'frequency' => 'customizado',
            'intervalo_personalizado_horas' => 6,
        ]);

        $plano = new Plano([
            'update_frequency' => 'customizado',
        ]);

        $proximoRastreamento = $service->calcularProximoRastreamento($projeto, $referencia, $plano);

        $this->assertSame('2026-04-14 16:00:00', $proximoRastreamento?->format('Y-m-d H:i:s'));
    }
}
