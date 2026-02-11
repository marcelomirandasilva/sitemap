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
        'pages_count',
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
        'images_count' => 'integer',
        'videos_count' => 'integer',
        'artifacts' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
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
