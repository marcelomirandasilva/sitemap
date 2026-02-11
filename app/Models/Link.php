<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sourcePage()
    {
        return $this->belongsTo(Page::class, 'source_page_id');
    }
}
