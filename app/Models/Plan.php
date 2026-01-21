<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'max_pages',
        'price_monthly_brl',
        'price_yearly_brl',
        'price_monthly_usd',
        'price_yearly_usd',
        'max_projects',
        'has_advanced_features',
    ];

    protected $casts = [
        'has_advanced_features' => 'boolean',
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

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
