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
        'price_monthly_brl' => 'integer',
        'price_yearly_brl' => 'integer',
        'price_monthly_usd' => 'integer',
        'price_yearly_usd' => 'integer',
    ];

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
