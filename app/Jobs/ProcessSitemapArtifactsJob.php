<?php

namespace App\Jobs;

use App\Models\Pagina;
use App\Models\TarefaSitemap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessSitemapArtifactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tarefa;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Tempo máximo de execução do Job (10 minutos)
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(TarefaSitemap $tarefa)
    {
        $this->tarefa = $tarefa;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jobId = $this->tarefa->external_job_id;
        $projectId = $this->tarefa->project_id;

        Log::info("Iniciando ingestão de artefatos para a tarefa: {$jobId} (Projeto: {$projectId})");

        // Formata os caminhos de forma similar ao SitemapGeneratorService
        $basePath = base_path('../api-sitemap/sitemaps/' . $jobId . '/');
        $projectPath = base_path('../api-sitemap/sitemaps/projects/' . $projectId . '/');

        // Limpar o cache de status de arquivo do Worker do PHP para caminhos idênticos
        clearstatcache();

        // Vamos procurar primeiro no projectPath/streams, depois na raiz, depois no basePath
        $filename = 'pages_stream.jsonl.gz';

        $path = $projectPath . 'streams/' . $filename;
        if (!file_exists($path)) {
            $path = $projectPath . $filename;
        }
        if (!file_exists($path)) {
            $path = $basePath . 'streams/' . $filename;
        }
        if (!file_exists($path)) {
            $path = $basePath . $filename;
        }

        if (!file_exists($path)) {
            Log::warning("Arquivo de páginas não encontrado para a tarefa {$jobId} no caminho: {$path}. Abortando inserção de páginas.");
            return;
        }

        // Antes de inserir as novas, limpa o que havia antes do mesmo projeto para evitar duplicidade de scans
        Pagina::where('project_id', $projectId)->delete();

        try {
            DB::beginTransaction();

            $handle = gzopen($path, 'r');
            if ($handle) {
                $batch = [];
                $batchSize = 2000;
                $count = 0;
                $now = now();

                while (($line = gzgets($handle)) !== false) {
                    $pageData = json_decode($line, true);

                    if (!$pageData || !isset($pageData['url'])) {
                        continue;
                    }

                    $batch[] = [
                        'project_id' => $projectId,
                        'url' => substr($pageData['url'], 0, 2048),
                        'path_hash' => hash('sha256', $pageData['url']),
                        'status_code' => $pageData['status_code'] ?? 200,
                        'title' => substr($pageData['title'] ?? '', 0, 255),
                        'priority' => $pageData['priority'] ?? '0.5',
                        'change_frequency' => $pageData['change_frequency'] ?? 'monthly',
                        'load_time_ms' => $pageData['load_time_ms'] ?? 0,
                        'content_type' => substr($pageData['content_type'] ?? 'text/html', 0, 100),
                        'size_bytes' => $pageData['size_bytes'] ?? 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $count++;

                    if (count($batch) >= $batchSize) {
                        Pagina::insert($batch);
                        $batch = [];
                        // Log para acompanhamento visual manual de performance (opcional)
                        // Log::debug("Inseridas {$count} páginas até o momento...");
                    }
                }

                if (!empty($batch)) {
                    Pagina::insert($batch);
                }

                gzclose($handle);
            }

            DB::commit();
            Log::info("Ingestão concluída com sucesso. Total de páginas inseridas: {$count}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro durante a ingestão do arquivo .jsonl: " . $e->getMessage());
            throw $e;
        }
    }
}
