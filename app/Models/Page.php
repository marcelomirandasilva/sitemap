<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
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

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function links()
    {
        return $this->hasMany(Link::class, 'source_page_id');
    }
}
