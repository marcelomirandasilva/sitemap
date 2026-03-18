<?php

namespace App\Services;

use App\Models\Projeto;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SitemapGeneratorService
{
    protected string $baseUrl;
    protected string $internalSecret;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.sitemap_generator.base_url');
        $this->internalSecret = config('services.sitemap_generator.internal_secret', '');
        $this->timeout = config('services.sitemap_generator.timeout', 3);

        Log::info("SitemapGeneratorService initialized with BaseURL: {$this->baseUrl}");
    }

    /**
     * Retorna os headers de autenticação interna para todas as requisições
     * server-to-server (Laravel → API Python).
     */
    protected function internalHeaders(int $userId, ?int $projectId = null): array
    {
        $headers = [
            'X-Internal-Token' => $this->internalSecret,
            'X-User-Id' => (string) $userId,
        ];

        if ($projectId !== null) {
            $headers['X-Project-Id'] = (string) $projectId;
        }

        return $headers;
    }

    /**
     * Inicia um novo Job de Sitemap para o projeto.
     */
    public function startJob(Projeto $projeto): ?string
    {
        $userId = $projeto->user_id;

        $payload = [
            'start_urls' => [$projeto->url],
            'max_depth' => $projeto->max_depth ?? 5,
            'max_pages' => $projeto->max_pages ?? 5000,
            'include_images' => (bool) $projeto->check_images,
            'include_videos' => (bool) $projeto->check_videos,
            'delay_between_requests' => (float) ($projeto->delay_between_requests ?? 1.0),
            'max_concurrent_requests' => (int) ($projeto->max_concurrent_requests ?? 10),
            'massive_processing' => true,
            'output_directory' => 'sitemaps/projects/' . $projeto->id,
        ];

        try {
            $response = Http::withHeaders($this->internalHeaders($userId, $projeto->id))
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/api/v1/sitemaps", $payload);

            if ($response->created() || $response->ok()) {
                $jobId = $response->json('job_id');

                $projeto->update(['current_crawler_job_id' => $jobId]);

                return $jobId;
            }

            Log::error('Falha ao criar job de sitemap', [
                'project_id' => $projeto->id,
                'response' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Erro de conexão ao criar job sitemap: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica o status de um Job existente.
     */
    public function checkStatus(string $jobId, int $userId): ?array
    {
        try {
            $response = Http::withHeaders($this->internalHeaders($userId))
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/api/v1/sitemaps/{$jobId}");

            if ($response->successful()) {
                $data = $response->json();

                if (($data['status'] ?? '') === 'completed') {
                    $finalArtifacts = [];

                    if (!empty($data['artifacts'])) {
                        foreach ($data['artifacts'] as $art) {
                            $finalArtifacts[] = [
                                'name' => $art['name'] ?? basename($art['path'] ?? 'artifact'),
                                'path' => $art['path'] ?? '',
                                'download_url' => route('downloads.sitemap', [
                                    'jobId' => $jobId,
                                    'filename' => $art['name'] ?? basename($art['path'] ?? 'artifact'),
                                ]),
                                'size_bytes' => $art['size_bytes'] ?? ($art['size'] ?? 0),
                            ];
                        }
                    } elseif (isset($data['result'])) {
                        $result = $data['result'];
                        $map = [
                            'main_sitemap_path' => ['name' => 'sitemap.xml', 'type' => 'main'],
                            'image_sitemap_path' => ['name' => 'sitemap_images.xml', 'type' => 'images'],
                            'video_sitemap_path' => ['name' => 'sitemap_videos.xml', 'type' => 'videos'],
                            'news_sitemap_path' => ['name' => 'sitemap_news.xml', 'type' => 'news'],
                            'mobile_sitemap_path' => ['name' => 'sitemap_mobile.xml', 'type' => 'mobile'],
                            'rss_path' => ['name' => 'rss.xml', 'type' => 'rss'],
                        ];

                        foreach ($map as $key => $info) {
                            if (!empty($result[$key])) {
                                $finalArtifacts[] = [
                                    'name' => $info['name'],
                                    'path' => $result[$key],
                                    'download_url' => route('downloads.sitemap', ['jobId' => $jobId, 'filename' => $info['name']]),
                                    'size_bytes' => 0,
                                ];
                            }
                        }
                    }

                    $data['artifacts'] = $finalArtifacts;
                }

                return $data;
            }

            if ($response->status() === 404) {
                return [
                    'status' => 'failed',
                    'progress' => 0,
                    'message' => 'Job removido ou não encontrado no servidor remoto.',
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Erro no checkStatus para job {$jobId}: " . $e->getMessage());
            return [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Falha na comunicação com o serviço de rastreamento.',
            ];
        }
    }

    /**
     * Recupera a lista de artefatos (arquivos) gerados pelo job.
     */
    public function getArtifacts(string $jobId, int $userId): array
    {
        try {
            $response = Http::withHeaders($this->internalHeaders($userId))
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/api/v1/sitemaps/{$jobId}/artifacts");

            if ($response->successful()) {
                return $response->json('artifacts') ?? [];
            }

            return [];

        } catch (\Exception $e) {
            Log::error("Erro ao buscar artefatos do job {$jobId}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém o conteúdo de um arquivo (do disco ou via API Proxy).
     */
    public function getFileContent(string $jobId, string $filename): ?string
    {
        $basePath = base_path('../api-sitemap/sitemaps/' . $jobId . '/');
        $filePath = $basePath . $filename;

        if (!file_exists($filePath) && !file_exists($filePath . '.zip')) {
            try {
                $job = \App\Models\TarefaSitemap::where('external_job_id', $jobId)->first();
                if ($job && $job->project_id) {
                    $projectPath = base_path('../api-sitemap/sitemaps/projects/' . $job->project_id . '/');
                    if (file_exists($projectPath . $filename) || file_exists($projectPath . $filename . '.zip')) {
                        $filePath = $projectPath . $filename;
                    }
                }
            } catch (\Exception $e) {
                // Silently fail DB lookup
            }
        }

        if (!file_exists($filePath) && !str_ends_with($filename, '.zip')) {
            if (file_exists($filePath . '.zip')) {
                $filePath .= '.zip';
            }
        }

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);

            if (str_ends_with($filePath, '.zip')) {
                if (class_exists('\ZipArchive')) {
                    $zip = new \ZipArchive();
                    if ($zip->open($filePath) === TRUE) {
                        $content = $zip->getFromIndex(0);
                        $zip->close();
                        return $content;
                    }
                }

                try {
                    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitemap_zip_' . uniqid();
                    mkdir($tempDir);
                    $cmd = sprintf('tar --force-local -xf %s -C %s', escapeshellarg($filePath), escapeshellarg($tempDir));
                    @shell_exec($cmd);
                    $files = glob($tempDir . DIRECTORY_SEPARATOR . '*');
                    if (!empty($files) && is_file($files[0])) {
                        $extracted = file_get_contents($files[0]);
                        if ($extracted && !str_starts_with($extracted, "PK\x03\x04")) {
                            $content = $extracted;
                        }
                    }
                    foreach ($files as $f)
                        if (is_file($f))
                            unlink($f);
                    rmdir($tempDir);
                    if ($content && !str_starts_with($content, "PK\x03\x04"))
                        return $content;
                } catch (\Exception $e) {
                    Log::error("Erro no fallback ZIP via tar: " . $e->getMessage());
                }

                try {
                    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitemap_ps_' . uniqid();
                    mkdir($tempDir);
                    $psCmd = sprintf('powershell -NoProfile -Command "Expand-Archive -Path \'%s\' -DestinationPath \'%s\' -Force"', $filePath, $tempDir);
                    @shell_exec($psCmd);
                    $files = glob($tempDir . DIRECTORY_SEPARATOR . '*');
                    if (!empty($files) && is_file($files[0])) {
                        $extracted = file_get_contents($files[0]);
                        if ($extracted && !str_starts_with($extracted, "PK\x03\x04")) {
                            $content = $extracted;
                        }
                    }
                    foreach ($files as $f)
                        if (is_file($f))
                            unlink($f);
                    rmdir($tempDir);
                    if ($content && !str_starts_with($content, "PK\x03\x04"))
                        return $content;
                } catch (\Exception $e) {
                    Log::error("Erro no fallback ZIP via PowerShell: " . $e->getMessage());
                }
            }

            return $content;
        }

        // Fallback via HTTP (sem userId no arquivo de conteúdo — usuário do contexto não disponível aqui)
        $response = $this->getArtifactFile($jobId, $filename, 0);
        if ($response && $response->successful()) {
            return $response->body();
        }

        return null;
    }

    /**
     * Obtém o conteúdo de um artefato via HTTP Proxy.
     */
    public function getArtifactFile(string $jobId, string $filename, int $userId = 0)
    {
        $url = "{$this->baseUrl}/api/v1/sitemaps/{$jobId}/artifacts/{$filename}";

        try {
            return Http::withHeaders($this->internalHeaders($userId))
                ->timeout($this->timeout)
                ->get($url);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar artefato via proxy ($url): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Testa a conectividade com a API usando o Token de Sistema.
     */
    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            $response = Http::withHeaders($this->internalHeaders(0))
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/api/v1/health");

            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Conexão com a API estabelecida via Token de Sistema.',
                    'duration_ms' => $duration,
                    'base_url' => $this->baseUrl,
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => false,
                'message' => 'API respondeu com erro.',
                'duration_ms' => $duration,
                'base_url' => $this->baseUrl,
                'status' => $response->status(),
                'response_body' => $response->body(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exceção: ' . $e->getMessage(),
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
                'base_url' => $this->baseUrl,
            ];
        }
    }

    /**
     * Busca URLs para preview a partir dos artefatos gerados.
     */
    public function getPreviewUrls(array $artifacts, ?string $jobId = null): array
    {
        $txtArtifact = collect($artifacts)->first(function ($a) {
            return str_ends_with($a['name'], '.txt') || str_ends_with($a['name'], '.txt.zip');
        });

        $xmlArtifact = collect($artifacts)->first(function ($a) {
            return str_ends_with($a['name'], 'sitemap.xml') || str_ends_with($a['name'], 'sitemap.xml.zip');
        });

        $previewArtifact = $txtArtifact ?: $xmlArtifact;

        if (!$previewArtifact) {
            return [];
        }

        try {
            $content = null;

            if ($jobId) {
                $content = $this->getFileContent($jobId, $previewArtifact['name']);
            }

            if (!$content && isset($previewArtifact['download_url'])) {
                try {
                    $r = Http::timeout($this->timeout)->get($previewArtifact['download_url']);
                    if ($r->successful())
                        $content = $r->body();
                } catch (\Exception $e) {
                    Log::warning("Preview fallback HTTP falhou: " . $e->getMessage());
                }
            }

            if (!$content) {
                return [];
            }

            $urls = [];

            if (str_contains($content, '<?xml') || str_contains($content, '<urlset')) {
                try {
                    $xml = simplexml_load_string($content);
                    if ($xml && isset($xml->url)) {
                        foreach ($xml->url as $urlItem) {
                            $urls[] = (string) $urlItem->loc;
                            if (count($urls) >= 10)
                                break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Erro ao parsear XML para preview: " . $e->getMessage());
                }
            } else {
                $lines = preg_split("/\r\n|\n|\r/", $content);
                $urls = collect($lines)->filter()->take(10)->map(fn($l) => trim($l))->values()->all();
            }

            if (empty($urls))
                return [];

            return collect($urls)->map(function ($url) {
                return [
                    'url' => $url,
                    'lastMod' => now()->toISOString(),
                    'priority' => '0.8000',
                    'freq' => 'daily',
                ];
            })->values()->all();

        } catch (\Exception $e) {
            Log::error("Erro ao gerar preview de URLs: " . $e->getMessage());
            return [];
        }
    }
}
