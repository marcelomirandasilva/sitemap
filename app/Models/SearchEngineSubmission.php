<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchEngineSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'provider',
        'site_identifier',
        'sitemap_url',
        'status',
        'message',
        'response_payload',
        'submitted_at',
    ];

    protected $casts = [
        'response_payload' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'project_id');
    }
}
