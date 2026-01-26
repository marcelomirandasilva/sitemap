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
        $this->timeout = config('services.sitemap_generator.timeout', 30);
    }

    /**
     * Obtém o token JWT de autenticação, usando cache.
     */
    protected function getToken(): ?string
    {
        return Cache::remember('sitemap_api_token', 1500, function () {
            try {
                $response = Http::asForm() // <--- ADICIONE ISTO
                    ->timeout($this->timeout)
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
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Erro ao verificar status do job {$jobId}: " . $e->getMessage());
            return null;
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
            return [];
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
}
