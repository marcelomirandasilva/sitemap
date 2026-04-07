<?php

namespace App\Jobs;

use App\Models\Link;
use App\Models\Pagina;
use App\Models\TarefaSitemap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Tempo maximo de execucao do job (10 minutos).
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

        Log::info("Iniciando ingestao de artefatos para a tarefa {$jobId} (Projeto {$projectId})");

        $filename = 'pages_stream.jsonl.gz';
        $arquivoTemporario = false;
        $path = $this->resolverCaminhoArquivo($jobId, $projectId, $filename);

        if (!$path) {
            Log::info("Arquivo local nao encontrado. Baixando via HTTP da API para a tarefa {$jobId}...");
            $path = $this->baixarArquivoViaHttp($jobId, $filename);
            $arquivoTemporario = true;
        }

        if (!$path) {
            Log::warning("Nao foi possivel obter o arquivo de paginas para a tarefa {$jobId}. Abortando ingestao.");
            return;
        }

        try {
            DB::beginTransaction();

            Pagina::where('project_id', $projectId)->delete();

            $handle = gzopen($path, 'r');
            $count = 0;
            $linksCount = 0;
            $now = now();

            if ($handle) {
                $batch = [];
                $batchSize = 2000;

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
                        'language' => isset($pageData['language']) ? substr((string) $pageData['language'], 0, 20) : null,
                        'meta_description' => $pageData['meta_description'] ?? null,
                        'canonical_url' => isset($pageData['canonical_url']) ? substr((string) $pageData['canonical_url'], 0, 2048) : null,
                        'hreflang_links' => $this->sanitizeHreflangLinks($pageData['hreflang_links'] ?? []),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $count++;

                    if (count($batch) >= $batchSize) {
                        Pagina::insert($batch);
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    Pagina::insert($batch);
                }

                gzclose($handle);

                $paginasPorChave = $this->carregarPaginasPorChave($projectId);
                $linksCount = $this->ingestLinksDoStream($path, $projectId, $paginasPorChave, $now);
            }

            DB::commit();
            Log::info("Ingestao concluida. Paginas inseridas: {$count}. Links inseridos: {$linksCount}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro durante a ingestao do arquivo jsonl: " . $e->getMessage());
            throw $e;
        } finally {
            if ($arquivoTemporario && $path && file_exists($path)) {
                @unlink($path);
                Log::info("Arquivo temporario removido: {$path}");
            }
        }
    }

    /**
     * Tenta localizar o arquivo no disco local (útil quando Laravel e API estão na mesma máquina).
     */
    protected function resolverCaminhoArquivo(string $jobId, int $projectId, string $filename): ?string
    {
        $basePath = base_path('../api-sitemap/sitemaps/' . $jobId . '/');
        $projectPath = base_path('../api-sitemap/sitemaps/projects/' . $projectId . '/');

        clearstatcache();

        $candidatos = [
            $projectPath . 'streams/' . $filename,
            $projectPath . $filename,
            $basePath . 'streams/' . $filename,
            $basePath . $filename,
        ];

        foreach ($candidatos as $candidato) {
            if (file_exists($candidato)) {
                Log::info("Arquivo encontrado no disco local: {$candidato}");
                return $candidato;
            }
        }

        return null;
    }

    /**
     * Baixa o arquivo via HTTP da API Python e salva em arquivo temporário.
     * Usa as configurações do .env: SITEMAP_API_URL, SITEMAP_INTERNAL_SECRET, SITEMAP_INGEST_DOWNLOAD_TIMEOUT.
     */
    protected function baixarArquivoViaHttp(string $jobId, string $filename): ?string
    {
        $baseUrl = config('services.sitemap_generator.base_url');
        $secret = config('services.sitemap_generator.internal_secret', '');
        $timeout = config('services.sitemap_generator.ingest_download_timeout', 300);

        $url = "{$baseUrl}/api/v1/sitemaps/{$jobId}/artifacts/{$filename}";

        Log::info("Baixando arquivo via HTTP: {$url} (timeout: {$timeout}s)");

        try {
            $response = Http::withHeaders([
                'X-Internal-Token' => $secret,
            ])
                ->timeout($timeout)
                ->get($url);

            if (!$response->successful()) {
                Log::error("Falha ao baixar arquivo via HTTP. Status: {$response->status()}. URL: {$url}");
                return null;
            }

            $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitemap_ingest_' . $jobId . '.jsonl.gz';
            file_put_contents($tempPath, $response->body());

            Log::info("Arquivo baixado com sucesso para: {$tempPath} (" . strlen($response->body()) . " bytes)");

            return $tempPath;

        } catch (\Exception $e) {
            Log::error("Erro ao baixar arquivo via HTTP para job {$jobId}: " . $e->getMessage());
            return null;
        }
    }

    protected function sanitizeHreflangLinks(mixed $links): array
    {
        if (!is_array($links)) {
            return [];
        }

        return collect($links)
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                $idioma = trim((string) ($item['lang'] ?? $item['hreflang'] ?? ''));
                $url = trim((string) ($item['url'] ?? $item['href'] ?? ''));

                if ($idioma === '' || $url === '') {
                    return null;
                }

                return [
                    'lang' => substr(mb_strtolower(str_replace('_', '-', $idioma)), 0, 20),
                    'url' => substr($url, 0, 2048),
                ];
            })
            ->filter()
            ->unique(fn(array $item) => $item['lang'] . '|' . $item['url'])
            ->values()
            ->all();
    }

    protected function carregarPaginasPorChave(int $projectId): array
    {
        return Pagina::where('project_id', $projectId)
            ->get(['id', 'url', 'status_code'])
            ->reduce(function (array $carry, Pagina $pagina) {
                $chave = $this->normalizeUrlKey($pagina->url);

                if ($chave) {
                    $carry[$chave] = [
                        'id' => $pagina->id,
                        'url' => $pagina->url,
                        'status_code' => (int) ($pagina->status_code ?? 0),
                    ];
                }

                return $carry;
            }, []);
    }

    protected function ingestLinksDoStream(string $path, int $projectId, array $paginasPorChave, $now): int
    {
        $handle = gzopen($path, 'r');

        if (!$handle) {
            return 0;
        }

        $batch = [];
        $batchSize = 2000;
        $count = 0;

        while (($line = gzgets($handle)) !== false) {
            $pageData = json_decode($line, true);

            if (!$pageData || empty($pageData['url'])) {
                continue;
            }

            $sourceUrl = $this->normalizeUrl((string) $pageData['url']);
            $sourceKey = $sourceUrl ? $this->normalizeUrlKey($sourceUrl) : null;
            $sourcePage = $sourceKey ? ($paginasPorChave[$sourceKey] ?? null) : null;

            if (!$sourcePage) {
                continue;
            }

            $links = $this->sanitizeOutgoingLinks(
                $sourceUrl,
                $pageData['outgoing_links'] ?? null,
                $pageData['content'] ?? null
            );

            foreach ($links as $link) {
                $targetUrl = $link['target_url'];
                $targetKey = $this->normalizeUrlKey($targetUrl);
                $targetPage = $targetKey ? ($paginasPorChave[$targetKey] ?? null) : null;
                $isExternal = (bool) $link['is_external'];
                $isBroken = !$isExternal && $targetPage && ((int) ($targetPage['status_code'] ?? 0) >= 400);

                $batch[] = [
                    'project_id' => $projectId,
                    'source_page_id' => $sourcePage['id'],
                    'target_url' => substr($targetUrl, 0, 2048),
                    'is_external' => $isExternal,
                    'is_broken' => $isBroken,
                    'anchor_text' => $link['anchor_text'] ? substr($link['anchor_text'], 0, 255) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $count++;

                if (count($batch) >= $batchSize) {
                    Link::insert($batch);
                    $batch = [];
                }
            }
        }

        if (!empty($batch)) {
            Link::insert($batch);
        }

        gzclose($handle);

        return $count;
    }

    protected function sanitizeOutgoingLinks(string $sourceUrl, mixed $links, mixed $content): array
    {
        if (is_array($links) && !empty($links)) {
            return collect($links)
                ->map(function ($item) use ($sourceUrl) {
                    if (!is_array($item)) {
                        return null;
                    }

                    $targetUrl = $this->normalizeUrl($item['url'] ?? $item['href'] ?? null);

                    if (!$targetUrl) {
                        return null;
                    }

                    return [
                        'target_url' => $targetUrl,
                        'anchor_text' => $this->normalizeAnchorText($item['anchor_text'] ?? null),
                        'is_external' => array_key_exists('is_external', $item)
                            ? (bool) $item['is_external']
                            : $this->isExternalLink($sourceUrl, $targetUrl),
                    ];
                })
                ->filter()
                ->unique(fn(array $item) => $item['target_url'] . '|' . ($item['anchor_text'] ?? '') . '|' . (int) $item['is_external'])
                ->values()
                ->all();
        }

        if (!is_string($content) || trim($content) === '' || !class_exists(\DOMDocument::class)) {
            return [];
        }

        $dom = new \DOMDocument();
        $previousState = libxml_use_internal_errors(true);

        try {
            $dom->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        } catch (\Throwable) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
            return [];
        }

        $items = [];

        foreach ($dom->getElementsByTagName('a') as $node) {
            $href = trim((string) $node->getAttribute('href'));

            if ($href === '') {
                continue;
            }

            $targetUrl = $this->resolveRelativeUrl($sourceUrl, $href);

            if (!$targetUrl) {
                continue;
            }

            $items[] = [
                'target_url' => $targetUrl,
                'anchor_text' => $this->normalizeAnchorText($node->textContent ?: null),
                'is_external' => $this->isExternalLink($sourceUrl, $targetUrl),
            ];
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        return collect($items)
            ->unique(fn(array $item) => $item['target_url'] . '|' . ($item['anchor_text'] ?? '') . '|' . (int) $item['is_external'])
            ->values()
            ->all();
    }

    protected function normalizeAnchorText(?string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return $value === '' ? null : $value;
    }

    protected function isExternalLink(string $sourceUrl, string $targetUrl): bool
    {
        $sourceHost = $this->normalizeHost(parse_url($sourceUrl, PHP_URL_HOST));
        $targetHost = $this->normalizeHost(parse_url($targetUrl, PHP_URL_HOST));

        return $sourceHost !== '' && $targetHost !== '' && $sourceHost !== $targetHost;
    }

    protected function normalizeHost(?string $host): string
    {
        return strtolower(preg_replace('/^www\./i', '', trim((string) $host)));
    }

    protected function normalizeUrlKey(?string $url): ?string
    {
        $normalized = $this->normalizeUrl($url);

        return $normalized ? hash('sha256', $normalized) : null;
    }

    protected function normalizeUrl(mixed $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        if ($path === '') {
            $path = '/';
        } elseif ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $scheme . '://' . $host . $port . $path . $query;
    }

    protected function resolveRelativeUrl(string $sourceUrl, string $href): ?string
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $href)) {
            return $this->normalizeUrl($href);
        }

        $base = parse_url($sourceUrl);

        if (!$base || empty($base['scheme']) || empty($base['host'])) {
            return null;
        }

        $origin = strtolower($base['scheme']) . '://' . strtolower($base['host']);

        if (!empty($base['port'])) {
            $origin .= ':' . $base['port'];
        }

        if (str_starts_with($href, '//')) {
            return $this->normalizeUrl(strtolower($base['scheme']) . ':' . $href);
        }

        if (str_starts_with($href, '/')) {
            return $this->normalizeUrl($origin . $href);
        }

        $basePath = $base['path'] ?? '/';
        $directory = str_contains($basePath, '/')
            ? preg_replace('/\/[^\/]*$/', '/', $basePath)
            : '/';

        return $this->normalizeUrl($origin . ($directory ?: '/') . $href);
    }
}
