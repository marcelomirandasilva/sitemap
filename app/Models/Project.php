<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'status',
        'frequency',
        'user_agent_custom',
        'delay_between_requests',
        'max_concurrent_requests',
        'current_crawler_job_id',
        'max_depth',
        'max_pages',
        'check_images',
        'check_videos',
        'last_crawled_at',
    ];

    protected $casts = [
        'check_images' => 'boolean',
        'check_videos' => 'boolean',
        'last_crawled_at' => 'datetime',
        'max_depth' => 'integer',
        'max_pages' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function links()
    {
        return $this->hasMany(Link::class);
    }

    public function sitemapJobs()
    {
        return $this->hasMany(SitemapJob::class);
    }
}
