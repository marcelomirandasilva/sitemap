<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pagina extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'project_id',
        'url',
        'path_hash',
        'status_code',
        'title',
        'priority',
        'change_frequency',
        'load_time_ms',
        'content_type',
        'size_bytes',
        'language',
        'meta_description',
        'canonical_url',
        'hreflang_links',
    ];

    protected $casts = [
        'hreflang_links' => 'array',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'project_id');
    }

    public function links()
    {
        return $this->hasMany(Link::class, 'source_page_id');
    }
}
