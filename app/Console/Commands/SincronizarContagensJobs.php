<?php

namespace App\Console\Commands;

use App\Models\SitemapJob;
use App\Services\SitemapGeneratorService;
use Illuminate\Console\Command;

class SincronizarContagensJobs extends Command
{
    protected $signature = 'jobs:sincronizar-contagens {--job-id= : ID específico do job para sincronizar}';
    protected $description = 'Sincroniza contagens de imagens e vídeos dos jobs existentes com a API Python';

    protected SitemapGeneratorService $sitemapService;

    public function __construct(SitemapGeneratorService $sitemapService)
    {
        parent::__construct();
        $this->sitemapService = $sitemapService;
    }

    public function handle()
    {
        $jobId = $this->option('job-id');

        if ($jobId) {
            $jobs = SitemapJob::where('id', $jobId)->get();
        } else {
            // Busca todos os jobs completed que têm images_count = 0
            $jobs = SitemapJob::where('status', 'completed')
                ->where(function ($query) {
                    $query->where('images_count', 0)
                        ->orWhereNull('images_count');
                })
                ->get();
        }

        if ($jobs->isEmpty()) {
            $this->info('Nenhum job para sincronizar.');
            return 0;
        }

        $this->info("Sincronizando {$jobs->count()} job(s)...");
        $bar = $this->output->createProgressBar($jobs->count());
        $bar->start();

        $atualizados = 0;
        $erros = 0;

        foreach ($jobs as $job) {
            try {
                $statusData = $this->sitemapService->checkStatus($job->external_job_id);

                if ($statusData) {
                    $imagesCount = $statusData['result']['total_images']
                        ?? $statusData['images_found']
                        ?? 0;

                    $videosCount = $statusData['result']['total_videos']
                        ?? $statusData['videos_found']
                        ?? 0;

                    $job->update([
                        'images_count' => $imagesCount,
                        'videos_count' => $videosCount,
                    ]);

                    if ($imagesCount > 0 || $videosCount > 0) {
                        $atualizados++;
                    }
                }
            } catch (\Exception $e) {
                $erros++;
                $this->newLine();
                $this->error("Erro no job {$job->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Sincronização concluída!");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Jobs processados', $jobs->count()],
                ['Jobs com mídia encontrada', $atualizados],
                ['Erros', $erros],
            ]
        );

        return 0;
    }
}
