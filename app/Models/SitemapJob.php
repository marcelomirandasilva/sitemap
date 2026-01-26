<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitemapJob extends Model
{
    protected $fillable = [
        'project_id',
        'external_job_id',
        'status',
        'progress',
        'artifacts',
        'message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'progress' => 'float',
        'artifacts' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
