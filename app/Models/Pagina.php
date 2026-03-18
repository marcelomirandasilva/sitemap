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
        'load_time_ms',
        'content_type',
        'size_bytes',
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
