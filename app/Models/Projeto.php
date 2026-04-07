<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Projeto extends Model
{
    use HasFactory;

    protected $table = 'projects';

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'published_sitemap_url',
        'google_site_property',
        'bing_site_url',
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
        'check_news',
        'check_mobile',
        'exclude_patterns',
        'crawl_policy_id',
        'compress_output',
        'enable_cache',
        'last_crawled_at',
        'next_scheduled_crawl_at',
    ];

    protected $casts = [
        'check_images' => 'boolean',
        'check_videos' => 'boolean',
        'check_news' => 'boolean',
        'check_mobile' => 'boolean',
        'compress_output' => 'boolean',
        'enable_cache' => 'boolean',
        'exclude_patterns' => 'array',
        'last_crawled_at' => 'datetime',
        'next_scheduled_crawl_at' => 'datetime',
        'max_depth' => 'integer',
        'max_pages' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paginas()
    {
        return $this->hasMany(Pagina::class, 'project_id');
    }

    public function links()
    {
        return $this->hasMany(Link::class, 'project_id');
    }

    public function tarefasSitemap()
    {
        return $this->hasMany(TarefaSitemap::class, 'project_id');
    }

    public function searchEngineSubmissions()
    {
        return $this->hasMany(SearchEngineSubmission::class, 'project_id');
    }
}
