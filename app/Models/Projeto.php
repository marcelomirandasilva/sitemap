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
}
