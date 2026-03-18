<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $table = 'links';
    protected $fillable = [
        'project_id',
        'source_page_id',
        'target_url',
        'is_external',
        'is_broken',
        'anchor_text',
    ];

    protected $casts = [
        'is_external' => 'boolean',
        'is_broken' => 'boolean',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'project_id');
    }

    public function paginaOrigem()
    {
        return $this->belongsTo(Pagina::class, 'source_page_id');
    }
}
