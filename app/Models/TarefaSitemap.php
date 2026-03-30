<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarefaSitemap extends Model
{
    protected $table = 'sitemap_jobs';
    protected $fillable = [
        'project_id',
        'external_job_id',
        'status',
        'progress',
        'pages_count',
        'urls_found',
        'urls_crawled',
        'urls_excluded',
        'images_count',
        'videos_count',
        'artifacts',
        'message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'progress' => 'float',
        'pages_count' => 'integer',
        'urls_found' => 'integer',
        'urls_crawled' => 'integer',
        'urls_excluded' => 'integer',
        'images_count' => 'integer',
        'videos_count' => 'integer',
        'artifacts' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function projeto()
    {
        return $this->belongsTo(Projeto::class, 'project_id');
    }

    /**
     * Accessor para garantir que as URLs de download apontem sempre para o Proxy do Laravel,
     * corrigindo retroativamente jobs antigos.
     */
    public function getArtifactsAttribute($value)
    {
        if (is_null($value))
            return [];

        // Como temos o Accessor, o Cast automático pode não ter ocorrido ainda ou o Laravel passa o valor raw.
        // Por segurança, decodificamos se for string.
        $artifacts = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($artifacts))
            return [];

        // Se não tiver external_job_id, não conseguimos gerar link
        if (empty($this->attributes['external_job_id'])) {
            return $artifacts;
        }

        $jobId = $this->attributes['external_job_id'];

        return array_map(function ($art) use ($jobId) {
            // Recalcula a URL de download para usar a rota interna
            if (isset($art['name'])) {
                // Remove extensões duplicadas se houver erro antigo no banco
                $filename = $art['name'];

                $art['download_url'] = route('downloads.sitemap', [
                    'jobId' => $jobId,
                    'filename' => $filename
                ]);
            }
            return $art;
        }, $artifacts);
    }
}
