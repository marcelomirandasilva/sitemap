<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plano extends Model
{
    use HasFactory;

    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'stripe_monthly_price_id',
        'stripe_yearly_price_id',
        'max_pages',
        'price_monthly_brl',
        'price_yearly_brl',
        'price_monthly_usd',
        'price_yearly_usd',
        'max_projects',
        'profundidade_maxima_padrao',
        'profundidade_maxima_limite',
        'concorrencia_padrao',
        'concorrencia_limite',
        'atraso_padrao_segundos',
        'atraso_minimo_segundos',
        'atraso_maximo_segundos',
        'has_advanced_features',
        'permite_imagens',
        'permite_videos',
        'permite_noticias',
        'permite_mobile',
        'permite_compactacao',
        'permite_cache_crawler',
        'permite_padroes_exclusao',
        'permite_politicas_crawl',
        'update_frequency',
        'intervalo_personalizado_padrao_horas',
        'ideal_for',
    ];

    protected $casts = [
        'has_advanced_features' => 'boolean',
        'permite_imagens' => 'boolean',
        'permite_videos' => 'boolean',
        'permite_noticias' => 'boolean',
        'permite_mobile' => 'boolean',
        'permite_compactacao' => 'boolean',
        'permite_cache_crawler' => 'boolean',
        'permite_padroes_exclusao' => 'boolean',
        'permite_politicas_crawl' => 'boolean',
        'max_pages' => 'integer',
        'max_projects' => 'integer',
        'profundidade_maxima_padrao' => 'integer',
        'profundidade_maxima_limite' => 'integer',
        'concorrencia_padrao' => 'integer',
        'concorrencia_limite' => 'integer',
        'atraso_padrao_segundos' => 'float',
        'atraso_minimo_segundos' => 'float',
        'atraso_maximo_segundos' => 'float',
        'intervalo_personalizado_padrao_horas' => 'integer',
        'price_monthly_brl' => 'integer',
        'price_yearly_brl' => 'integer',
        'price_monthly_usd' => 'integer',
        'price_yearly_usd' => 'integer',
    ];

    public function profundidadeMaximaLimiteEfetiva(): int
    {
        return max(1, min((int) ($this->profundidade_maxima_limite ?? 3), 10));
    }

    public function profundidadeMaximaPadraoEfetiva(): int
    {
        return max(1, min((int) ($this->profundidade_maxima_padrao ?? 3), $this->profundidadeMaximaLimiteEfetiva()));
    }

    public function concorrenciaLimiteEfetiva(): int
    {
        return max(1, min((int) ($this->concorrencia_limite ?? 2), 100));
    }

    public function concorrenciaPadraoEfetiva(): int
    {
        return max(1, min((int) ($this->concorrencia_padrao ?? 2), $this->concorrenciaLimiteEfetiva()));
    }

    public function atrasoMinimoEfetivo(): float
    {
        return max(0.1, (float) ($this->atraso_minimo_segundos ?? 1.0));
    }

    public function atrasoMaximoEfetivo(): float
    {
        return max($this->atrasoMinimoEfetivo(), (float) ($this->atraso_maximo_segundos ?? 1.0));
    }

    public function atrasoPadraoEfetivo(): float
    {
        $atrasoPadrao = (float) ($this->atraso_padrao_segundos ?? 1.0);

        return max($this->atrasoMinimoEfetivo(), min($atrasoPadrao, $this->atrasoMaximoEfetivo()));
    }

    public function intervaloPersonalizadoPadraoHorasEfetivo(): int
    {
        return max(1, min((int) ($this->intervalo_personalizado_padrao_horas ?? 24), 720));
    }

    public function limitePaginasEfetivo(int $limiteMaximoApi): int
    {
        $limitePlano = (int) ($this->max_pages ?? $limiteMaximoApi);

        if ($limitePlano === -1) {
            return $limiteMaximoApi;
        }

        return max(1, min($limitePlano, $limiteMaximoApi));
    }

    // Helpers BRL
    public function getMonthlyPriceBrlAttribute()
    {
        return $this->price_monthly_brl / 100;
    }

    public function getYearlyPriceBrlAttribute()
    {
        return $this->price_yearly_brl / 100;
    }

    // Helpers USD
    public function getMonthlyPriceUsdAttribute()
    {
        return $this->price_monthly_usd / 100;
    }

    public function getYearlyPriceUsdAttribute()
    {
        return $this->price_yearly_usd / 100;
    }

    public function assinaturas()
    {
        return $this->hasMany(Assinatura::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
