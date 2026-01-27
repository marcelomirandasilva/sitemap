<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SitemapGeneratorService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.sitemap_generator.base_url');
        $this->username = config('services.sitemap_generator.username');
        $this->password = config('services.sitemap_generator.password');
        // Timeout reduzido para evitar travamento da sessão PHP em polling
        $this->timeout = config('services.sitemap_generator.timeout', 3);
        Log::info("SitemapGeneratorService initialized with BaseURL: {$this->baseUrl}");
    }

    /**
     * Obtém o token JWT de autenticação, usando cache.
     */
    protected function getToken(): ?string
    {
        return Cache::remember('sitemap_api_token', 1500, function () {
            try {
                $response = Http::timeout($this->timeout)
                    ->post("{$this->baseUrl}/api/auth/token", [
                        'username' => $this->username,
                        'password' => $this->password,
                    ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('Falha na autenticação Sitemaps API', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Erro de conexão: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Inicia um novo Job de Sitemap para o projeto.
     */
    public function startJob(Project $project): ?string
    {
        $token = $this->getToken();

        if (!$token) {
            throw new \Exception("Não foi possível autenticar na API de Sitemaps.");
        }

        $payload = [
            'start_urls' => [$project->url],
            'max_depth' => $project->max_depth ?? 3,
            'max_pages' => $project->max_pages ?? 1000,
            'include_images' => $project->check_images ?? true,
            'include_videos' => $project->check_videos ?? true,
            // 'webhook_url' => route('api.webhook.sitemap.finished'), // Futuro
        ];

        try {
            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/api/v1/sitemaps", $payload);

            if ($response->created() || $response->ok()) {
                return $response->json('job_id');
            }

            Log::error('Falha ao criar job de sitemap', ['project_id' => $project->id, 'response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Erro de conexão ao criar job sitemap: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verifica o status de um Job existente.
     */
    public function checkStatus(string $jobId): ?array
    {
        $token = $this->getToken();

        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/api/v1/sitemaps/{$jobId}");

            if ($response->successful()) {
                $data = $response->json();

                // Tratamento de Artifacts
                if (($data['status'] ?? '') === 'completed') {
                    $finalArtifacts = [];

                    // Se a API retornou artifacts nativos, vamos processá-los primeiro para converter as URLs para nosso proxy
                    if (!empty($data['artifacts'])) {
                        foreach ($data['artifacts'] as $art) {
                            $finalArtifacts[] = [
                                'name' => $art['name'] ?? basename($art['path'] ?? 'artifact'),
                                'path' => $art['path'] ?? '',
                                'download_url' => route('downloads.sitemap', [
                                    'jobId' => $jobId,
                                    'filename' => $art['name'] ?? basename($art['path'] ?? 'artifact')
                                ]),
                                'size_bytes' => $art['size_bytes'] ?? ($art['size'] ?? 0),
                            ];
                        }
                    }
                    // Se não retornou nativos mas tem o objeto result, fazemos o Polyfill
                    elseif (isset($data['result'])) {
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

            // Se for 404, o job não existe mais -> Falha definitiva
            if ($response->status() === 404) {
                return [
                    'status' => 'failed',
                    'progress' => 0,
                    'message' => 'Job removido ou não encontrado no servidor remoto.'
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Erro no checkStatus para job {$jobId}. URL: {$this->baseUrl}/api/v1/sitemaps/{$jobId}. Erro: " . $e->getMessage());
            // Retorna falha para que o Controller possa atualizar o status e parar o polling
            return [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Erro ao comunicar com o serviço de sitemap: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Recupera a lista de artefatos (arquivos) gerados pelo job.
     */
    public function getArtifacts(string $jobId): array
    {
        $token = $this->getToken();

        if (!$token) {
            return [];
        }

        try {
            $response = Http::withToken($token)
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
     * Tenta obter o conteúdo de um arquivo (do disco ou via API).
     */
    public function getFileContent(string $jobId, string $filename): ?string
    {
        // 1. Tentar disco direto (Mais rápido e confiável localmente)
        $basePath = base_path('../api-sitemap/sitemaps/' . $jobId . '/');
        $filePath = $basePath . $filename;

        // Fallback para .zip se o arquivo solicitado sem .zip não existir
        if (!file_exists($filePath) && !str_ends_with($filename, '.zip')) {
            if (file_exists($filePath . '.zip')) {
                $filePath .= '.zip';
            }
        }

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);

            // Se for ZIP, precisamos descompactar o conteúdo real
            if (str_ends_with($filePath, '.zip')) {
                // Tenta ZipArchive nativo primeiro
                if (class_exists('\\ZipArchive')) {
                    $zip = new \ZipArchive();
                    if ($zip->open($filePath) === TRUE) {
                        $content = $zip->getFromIndex(0);
                        $zip->close();
                        return $content;
                    }
                }

                // Fallback para Windows (Windows 10/11 possuem o comando 'tar' nativo que extrai zip)
                try {
                    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitemap_zip_' . uniqid();
                    mkdir($tempDir);

                    // A flag --force-local impede que o tar interprete ":" como um host remoto (ex: D:\...)
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
                    Log::error("Erro no fallback de extração ZIP via tar: " . $e->getMessage());
                }

                // Fallback 2: PowerShell (Altamente confiável no Windows 11)
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
                    Log::error("Erro no fallback de extração ZIP via PS: " . $e->getMessage());
                }
            }

            return $content;
        }

        // 2. Fallback via API HTTP (Proxy/Remoto)
        $response = $this->getArtifactFile($jobId, $filename);
        if ($response && $response->successful()) {
            return $response->body();
        }

        return null;
    }

    /**
     * Obtém o conteúdo de um artefato da API Python (Proxy).
     */
    public function getArtifactFile(string $jobId, string $filename)
    {
        $token = $this->getToken();
        if (!$token)
            return null;

        // Na v1 a rota é /api/v1/sitemaps/{job_id}/artifacts/{name}
        $url = "{$this->baseUrl}/api/v1/sitemaps/{$jobId}/artifacts/{$filename}";

        try {
            return Http::withToken($token)
                ->timeout($this->timeout)
                ->get($url);
        } catch (\Exception $e) {
            Log::error("Erro ao buscar artefato via proxy ($url): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Baixa o conteúdo de um artefato específico (Método legado/helper).
     */
    public function fetchArtifactContent(string $url): ?string
    {
        // Se a URL for do próprio domínio (ex: em dev local), pode ter problemas de DNS interno
        // Mas assumindo que a API está em outra porta/serviço.

        // Pequena validação para garantir que estamos baixando da API confiável
        if (!str_starts_with($url, $this->baseUrl)) {
            // Log::warning("Tentativa de baixar artefato de URL externa: $url");
            // return null; 
            // Comentado pois o s3/minio pode ter outra URL. Vamos confiar na URL salva no banco por enquanto.
        }

        try {
            $response = Http::timeout($this->timeout)
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::error("Falha ao baixar conteúdo do artefato: $url", ['status' => $response->status()]);
            return null;
        } catch (\Exception $e) {
            Log::error("Erro ao baixar artefato $url: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Testa user/pass e connectivity com a API.
     */
    public function testConnection(): array
    {
        $start = microtime(true);
        try {
            // Realiza a requisição explicitamente para capturar detalhes
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/auth/token", [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            $duration = round((microtime(true) - $start) * 1000, 2);
            $data = $response->json();

            if ($response->successful() && isset($data['access_token'])) {
                return [
                    'success' => true,
                    'message' => 'Autenticação bem-sucedida! Token recebido.',
                    'token_preview' => substr($data['access_token'], 0, 15) . '...',
                    'duration_ms' => $duration,
                    'base_url' => $this->baseUrl,
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Falha ao autenticar.',
                'duration_ms' => $duration,
                'base_url' => $this->baseUrl,
                'status' => $response->status(),
                'response_body' => $data ?? $response->body(),
                'debug_creds' => [
                    'user' => $this->username,
                    'pass_len' => strlen($this->password),
                ]
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
     * Busca o conteúdo do urllist.txt ou sitemap.xml e retorna um array formatado para o preview.
     */
    public function getPreviewUrls(array $artifacts, string $jobId = null): array
    {
        // 1. Tentar urllist.txt (preferencial)
        $txtArtifact = collect($artifacts)->firstWhere(function ($a) {
            return str_ends_with($a['name'], '.txt') || str_ends_with($a['name'], '.txt.zip');
        });

        // 2. Tentar sitemap.xml (fallback)
        $xmlArtifact = collect($artifacts)->firstWhere(function ($a) {
            return str_ends_with($a['name'], 'sitemap.xml') || str_ends_with($a['name'], 'sitemap.xml.zip');
        });

        $previewArtifact = $txtArtifact ?: $xmlArtifact;

        if (!$previewArtifact) {
            return [];
        }

        try {
            $content = null;

            // Se tivermos jobId, tentamos via sistema de arquivos direto primeiro
            if ($jobId) {
                $content = $this->getFileContent($jobId, $previewArtifact['name']);
            }

            // Fallback para URL se falhou o disco ou não temos jobId
            if (!$content && isset($previewArtifact['download_url'])) {
                $content = $this->fetchArtifactContent($previewArtifact['download_url']);
            }

            if (!$content) {
                return [];
            }

            $urls = [];

            // Identificar se o conteúdo é XML ou TXT
            if (str_contains($content, '<?xml') || str_contains($content, '<urlset')) {
                // Parse XML para extrair <loc>
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
                // Tratamento padrão TXT
                $lines = preg_split("/\r\n|\n|\r/", $content);
                $urls = collect($lines)->filter()->take(10)->map(fn($l) => trim($l))->values()->all();
            }

            if (empty($urls))
                return [];

            return collect($urls)
                ->map(function ($url) {
                    return [
                        'url' => $url,
                        'lastMod' => now()->toISOString(),
                        'priority' => '0.8000',
                        'freq' => 'daily'
                    ];
                })
                ->values()
                ->all();
        } catch (\Exception $e) {
            Log::error("Erro ao gerar preview de URLs: " . $e->getMessage());
            return [];
        }
    }
}
