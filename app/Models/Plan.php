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
        'price',
        'max_projects',
        'max_pages_per_sitemap',
        'has_image_sitemap',
    ];

    protected $casts = [
        'has_image_sitemap' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
