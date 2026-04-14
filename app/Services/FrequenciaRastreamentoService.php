<?php

namespace App\Services;

use App\Models\Plano;
use App\Models\Projeto;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class FrequenciaRastreamentoService
{
    public const INTERVALO_PERSONALIZADO_MINIMO_HORAS = 1;
    public const INTERVALO_PERSONALIZADO_MAXIMO_HORAS = 720;
    public const INTERVALO_PERSONALIZADO_PADRAO_HORAS = 24;

    public function normalizarFrequencia(?string $frequencia): string
    {
        $normalizada = mb_strtolower(trim((string) $frequencia));

        return match (true) {
            str_contains($normalizada, 'manual') => 'manual',
            str_contains($normalizada, 'custom') || str_contains($normalizada, 'person') => 'customizado',
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
            'customizado' => 6,
        ];

        $ordemExibicao = ['manual', 'diario', 'semanal', 'quinzenal', 'mensal', 'anual', 'customizado'];
        $frequenciaNormalizada = $this->normalizarFrequencia($frequenciaPlano);
        $nivelPlano = $niveis[$frequenciaNormalizada] ?? 0;

        return array_values(array_filter($ordemExibicao, function ($valor) use ($niveis, $nivelPlano) {
            return ($niveis[$valor] ?? 0) <= $nivelPlano || $valor === 'manual';
        }));
    }

    public function limitesIntervaloPersonalizadoHoras(): array
    {
        return [
            'minimo' => self::INTERVALO_PERSONALIZADO_MINIMO_HORAS,
            'maximo' => self::INTERVALO_PERSONALIZADO_MAXIMO_HORAS,
            'padrao' => self::INTERVALO_PERSONALIZADO_PADRAO_HORAS,
        ];
    }

    public function normalizarIntervaloPersonalizadoHoras(?int $intervalo): int
    {
        $limites = $this->limitesIntervaloPersonalizadoHoras();
        $intervaloNormalizado = (int) ($intervalo ?? $limites['padrao']);

        if ($intervaloNormalizado < $limites['minimo']) {
            return $limites['minimo'];
        }

        if ($intervaloNormalizado > $limites['maximo']) {
            return $limites['maximo'];
        }

        return $intervaloNormalizado;
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
            'customizado' => $base->copy()->addHours($this->normalizarIntervaloPersonalizadoHoras($projeto->intervalo_personalizado_horas)),
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
        $atributosAtualizados = [
            'frequency' => $frequenciaEfetiva,
        ];

        if ($frequenciaEfetiva === 'customizado') {
            $atributosAtualizados['intervalo_personalizado_horas'] = $this->normalizarIntervaloPersonalizadoHoras($projeto->intervalo_personalizado_horas);
        } elseif ($projeto->intervalo_personalizado_horas !== null) {
            $atributosAtualizados['intervalo_personalizado_horas'] = null;
        }

        $houveMudanca = false;

        foreach ($atributosAtualizados as $campo => $valor) {
            if ($projeto->{$campo} !== $valor) {
                $houveMudanca = true;
                break;
            }
        }

        if ($houveMudanca) {
            $projeto->forceFill($atributosAtualizados)->saveQuietly();
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
