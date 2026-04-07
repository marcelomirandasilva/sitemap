<?php

namespace App\Services;

use App\Models\Plano;
use App\Models\Projeto;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class FrequenciaRastreamentoService
{
    public function normalizarFrequencia(?string $frequencia): string
    {
        $normalizada = mb_strtolower(trim((string) $frequencia));

        return match (true) {
            str_contains($normalizada, 'manual') => 'manual',
            str_contains($normalizada, 'di') => 'diario',
            str_contains($normalizada, 'seman') => 'semanal',
            str_contains($normalizada, 'quinzen') => 'quinzenal',
            str_contains($normalizada, 'mens') => 'mensal',
            str_contains($normalizada, 'anual') => 'anual',
            default => 'manual',
        };
    }

    public function frequenciasPermitidasParaPlano(?string $frequenciaPlano): array
    {
        $niveis = [
            'manual' => 0,
            'anual' => 1,
            'mensal' => 2,
            'quinzenal' => 3,
            'semanal' => 4,
            'diario' => 5,
        ];

        $ordemExibicao = ['manual', 'diario', 'semanal', 'quinzenal', 'mensal', 'anual'];
        $frequenciaNormalizada = $this->normalizarFrequencia($frequenciaPlano);
        $nivelPlano = $niveis[$frequenciaNormalizada] ?? 0;

        return array_values(array_filter($ordemExibicao, function ($valor) use ($niveis, $nivelPlano) {
            return ($niveis[$valor] ?? 0) <= $nivelPlano || $valor === 'manual';
        }));
    }

    public function frequenciaEfetivaProjeto(Projeto $projeto, ?Plano $plano = null): string
    {
        $plano ??= $projeto->user?->planoEfetivo();

        $frequenciaProjeto = $this->normalizarFrequencia($projeto->frequency);
        $frequenciasPermitidas = $this->frequenciasPermitidasParaPlano($plano?->update_frequency);

        if (in_array($frequenciaProjeto, $frequenciasPermitidas, true)) {
            return $frequenciaProjeto;
        }

        $frequenciaPlano = $this->normalizarFrequencia($plano?->update_frequency);

        if (in_array($frequenciaPlano, $frequenciasPermitidas, true)) {
            return $frequenciaPlano;
        }

        return 'manual';
    }

    public function calcularProximoRastreamento(
        Projeto $projeto,
        ?CarbonInterface $referencia = null,
        ?Plano $plano = null
    ): ?CarbonInterface {
        $frequencia = $this->frequenciaEfetivaProjeto($projeto, $plano);

        if ($frequencia === 'manual') {
            return null;
        }

        $base = $referencia
            ?? $projeto->last_crawled_at
            ?? $projeto->created_at
            ?? Carbon::now();

        $base = Carbon::instance($base instanceof Carbon ? $base : $base->toDateTime());

        return match ($frequencia) {
            'diario' => $base->copy()->addDay(),
            'semanal' => $base->copy()->addWeek(),
            'quinzenal' => $base->copy()->addDays(14),
            'mensal' => $base->copy()->addMonth(),
            'anual' => $base->copy()->addYear(),
            default => null,
        };
    }

    public function sincronizarFrequenciaProjeto(Projeto $projeto, ?Plano $plano = null): Projeto
    {
        $frequenciaEfetiva = $this->frequenciaEfetivaProjeto($projeto, $plano);

        if ($projeto->frequency !== $frequenciaEfetiva) {
            $projeto->forceFill([
                'frequency' => $frequenciaEfetiva,
            ])->saveQuietly();
        }

        return $projeto->refresh();
    }

    public function garantirProximoRastreamento(Projeto $projeto, ?Plano $plano = null): Projeto
    {
        $frequenciaAnterior = $this->normalizarFrequencia($projeto->frequency);
        $projeto = $this->sincronizarFrequenciaProjeto($projeto, $plano);

        if ($projeto->frequency === 'manual') {
            if ($projeto->next_scheduled_crawl_at !== null) {
                $projeto->forceFill([
                    'next_scheduled_crawl_at' => null,
                ])->saveQuietly();
            }

            return $projeto->refresh();
        }

        if ($frequenciaAnterior !== $projeto->frequency) {
            return $this->atualizarProximoRastreamento($projeto, null, $plano);
        }

        if ($projeto->next_scheduled_crawl_at) {
            return $projeto;
        }

        return $this->atualizarProximoRastreamento($projeto, null, $plano);
    }

    public function atualizarProximoRastreamento(
        Projeto $projeto,
        ?CarbonInterface $referencia = null,
        ?Plano $plano = null
    ): Projeto {
        $projeto = $this->sincronizarFrequenciaProjeto($projeto, $plano);
        $proximoRastreamento = $this->calcularProximoRastreamento($projeto, $referencia, $plano);

        $valorAtual = $projeto->next_scheduled_crawl_at?->toISOString();
        $novoValor = $proximoRastreamento?->toISOString();

        if ($valorAtual !== $novoValor) {
            $projeto->forceFill([
                'next_scheduled_crawl_at' => $proximoRastreamento,
            ])->saveQuietly();
        }

        return $projeto->refresh();
    }
}
