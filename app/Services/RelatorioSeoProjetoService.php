<?php

namespace App\Services;

use App\Models\Projeto;
use DOMDocument;
use Illuminate\Support\Collection;

class RelatorioSeoProjetoService
{
    public function montarParaProjeto(Projeto $projeto): array
    {
        $dados = $this->carregarDoStream($projeto);

        if ($dados['paginas']->isEmpty()) {
            $dados = $this->carregarDoBanco($projeto);
        }

        if ($dados['paginas']->isEmpty()) {
            return $this->relatorioVazio();
        }

        return $this->montarRelatorio(
            $dados['paginas'],
            $dados['links'],
            $dados['fonte']
        );
    }

    protected function carregarDoBanco(Projeto $projeto): array
    {
        $paginas = $projeto->paginas()
            ->select(['id', 'url', 'title', 'status_code'])
            ->orderBy('id')
            ->get()
            ->map(fn ($pagina) => [
                'url' => $pagina->url,
                'title' => $pagina->title,
                'status_code' => (int) ($pagina->status_code ?? 0),
            ]);

        $links = $projeto->links()
            ->with('paginaOrigem:id,url,title')
            ->orderBy('id')
            ->get()
            ->map(function ($link) {
                return [
                    'source_url' => $link->paginaOrigem?->url,
                    'source_title' => $link->paginaOrigem?->title,
                    'target_url' => $link->target_url,
                    'anchor_text' => $link->anchor_text,
                    'is_external' => (bool) $link->is_external,
                ];
            })
            ->filter(fn (array $link) => !empty($link['source_url']) && !empty($link['target_url']))
            ->values();

        return [
            'fonte' => 'banco',
            'paginas' => $paginas,
            'links' => $links,
        ];
    }

    protected function carregarDoStream(Projeto $projeto): array
    {
        $caminho = $this->encontrarPagesStream($projeto);

        if (!$caminho) {
            return [
                'fonte' => 'stream',
                'paginas' => collect(),
                'links' => collect(),
            ];
        }

        $paginas = [];
        $links = [];
        $handle = @gzopen($caminho, 'r');

        if (!$handle) {
            return [
                'fonte' => 'stream',
                'paginas' => collect(),
                'links' => collect(),
            ];
        }

        while (($linha = gzgets($handle)) !== false) {
            $dados = json_decode($linha, true);

            if (!is_array($dados) || empty($dados['url'])) {
                continue;
            }

            $url = (string) $dados['url'];
            $titulo = $dados['title'] ?? null;

            $paginas[] = [
                'url' => $url,
                'title' => $titulo,
                'status_code' => (int) ($dados['status_code'] ?? 0),
            ];

            foreach ($this->normalizarLinksSaida($url, $dados['outgoing_links'] ?? null, $dados['content'] ?? null) as $link) {
                $links[] = [
                    'source_url' => $url,
                    'source_title' => $titulo,
                    'target_url' => $link['target_url'],
                    'anchor_text' => $link['anchor_text'],
                    'is_external' => $link['is_external'],
                ];
            }
        }

        gzclose($handle);

        return [
            'fonte' => 'stream',
            'paginas' => collect($paginas),
            'links' => collect($links),
        ];
    }

    protected function encontrarPagesStream(Projeto $projeto): ?string
    {
        $ultimoJob = $projeto->tarefasSitemap()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        $caminhos = [
            base_path('../api-sitemap/sitemaps/projects/' . $projeto->id . '/streams/pages_stream.jsonl.gz'),
            base_path('../api-sitemap/sitemaps/projects/' . $projeto->id . '/pages_stream.jsonl.gz'),
        ];

        if ($ultimoJob?->external_job_id) {
            $caminhos[] = base_path('../api-sitemap/sitemaps/' . $ultimoJob->external_job_id . '/streams/pages_stream.jsonl.gz');
            $caminhos[] = base_path('../api-sitemap/sitemaps/' . $ultimoJob->external_job_id . '/pages_stream.jsonl.gz');
        }

        foreach ($caminhos as $caminho) {
            if (is_file($caminho)) {
                return $caminho;
            }
        }

        return null;
    }

    protected function montarRelatorio(Collection $paginas, Collection $links, string $fonte): array
    {
        $paginasPorChave = [];
        $contagemEntrada = [];
        $contagemSaidaInterna = [];
        $distribuicaoProfundidade = [];
        $diretoriosPrincipais = [];
        $profundidadeMaxima = 0;

        foreach ($paginas as $pagina) {
            $url = $this->normalizarUrl($pagina['url'] ?? null);

            if (!$url) {
                continue;
            }

            $chave = $this->chaveUrl($url);
            $profundidade = $this->calcularProfundidade($url);
            $diretorio = $this->diretorioPrincipal($url);

            $paginasPorChave[$chave] = [
                'url' => $url,
                'title' => $pagina['title'] ?? null,
                'status_code' => (int) ($pagina['status_code'] ?? 0),
                'profundidade' => $profundidade,
                'diretorio_principal' => $diretorio,
            ];

            $contagemEntrada[$chave] = 0;
            $contagemSaidaInterna[$chave] = 0;
            $distribuicaoProfundidade[$profundidade] = ($distribuicaoProfundidade[$profundidade] ?? 0) + 1;
            $diretoriosPrincipais[$diretorio] = ($diretoriosPrincipais[$diretorio] ?? 0) + 1;
            $profundidadeMaxima = max($profundidadeMaxima, $profundidade);
        }

        if (empty($paginasPorChave)) {
            return $this->relatorioVazio();
        }

        $linksQuebrados = [];
        $linksExternos = [];
        $paginasComQuebrados = [];
        $totalLinksInternos = 0;
        $totalLinksExternos = 0;

        foreach ($links as $link) {
            $sourceUrl = $this->normalizarUrl($link['source_url'] ?? null);
            $targetUrl = $this->normalizarUrl($link['target_url'] ?? null);

            if (!$sourceUrl || !$targetUrl) {
                continue;
            }

            $sourceKey = $this->chaveUrl($sourceUrl);

            if (!isset($paginasPorChave[$sourceKey])) {
                continue;
            }

            $anchorText = $this->normalizarAnchor($link['anchor_text'] ?? null);
            $isExternal = array_key_exists('is_external', $link)
                ? (bool) $link['is_external']
                : $this->isExternalLink($sourceUrl, $targetUrl);

            if ($isExternal) {
                $totalLinksExternos++;
                $grupo = $linksExternos[$targetUrl] ?? [
                    'target_url' => $targetUrl,
                    'dominio' => parse_url($targetUrl, PHP_URL_HOST) ?: $targetUrl,
                    'ocorrencias' => 0,
                    'source_url' => $sourceUrl,
                    'source_title' => $link['source_title'] ?? null,
                    'anchor_text' => $anchorText,
                ];

                $grupo['ocorrencias']++;
                $linksExternos[$targetUrl] = $grupo;
                continue;
            }

            $totalLinksInternos++;
            $contagemSaidaInterna[$sourceKey] = ($contagemSaidaInterna[$sourceKey] ?? 0) + 1;

            $targetKey = $this->chaveUrl($targetUrl);

            if (!isset($paginasPorChave[$targetKey])) {
                continue;
            }

            $contagemEntrada[$targetKey] = ($contagemEntrada[$targetKey] ?? 0) + 1;
            $statusDestino = (int) ($paginasPorChave[$targetKey]['status_code'] ?? 0);

            if ($statusDestino >= 400) {
                $paginasComQuebrados[$sourceKey] = true;
                $linksQuebrados[] = [
                    'source_url' => $sourceUrl,
                    'source_title' => $link['source_title'] ?? null,
                    'target_url' => $targetUrl,
                    'anchor_text' => $anchorText,
                    'status_code' => $statusDestino,
                ];
            }
        }

        arsort($diretoriosPrincipais);
        ksort($distribuicaoProfundidade);

        $paginasSemEntradaCollection = collect($paginasPorChave)
            ->filter(function (array $pagina, string $chave) use ($contagemEntrada) {
                return $pagina['profundidade'] > 0 && (($contagemEntrada[$chave] ?? 0) === 0);
            });

        $paginasSemEntrada = $paginasSemEntradaCollection
            ->sortByDesc('profundidade')
            ->take(20)
            ->map(fn (array $pagina) => [
                'url' => $pagina['url'],
                'title' => $pagina['title'],
                'profundidade' => $pagina['profundidade'],
            ])
            ->values()
            ->all();

        $paginasSemSaidaCollection = collect($paginasPorChave)
            ->filter(fn (array $pagina, string $chave) => (($contagemSaidaInterna[$chave] ?? 0) === 0));

        $paginasSemSaida = $paginasSemSaidaCollection
            ->sortByDesc('profundidade')
            ->take(20)
            ->map(fn (array $pagina) => [
                'url' => $pagina['url'],
                'title' => $pagina['title'],
                'profundidade' => $pagina['profundidade'],
            ])
            ->values()
            ->all();

        $paginasMaisReferenciadas = collect($paginasPorChave)
            ->map(function (array $pagina, string $chave) use ($contagemEntrada) {
                return [
                    'url' => $pagina['url'],
                    'title' => $pagina['title'],
                    'total' => $contagemEntrada[$chave] ?? 0,
                ];
            })
            ->filter(fn (array $pagina) => $pagina['total'] > 0)
            ->sortByDesc('total')
            ->take(20)
            ->values()
            ->all();

        return [
            'disponivel' => true,
            'fonte' => $fonte,
            'total_paginas' => count($paginasPorChave),
            'total_links' => $totalLinksInternos + $totalLinksExternos,
            'total_links_internos' => $totalLinksInternos,
            'total_links_externos' => $totalLinksExternos,
            'total_links_quebrados' => count($linksQuebrados),
            'paginas_com_links_quebrados' => count($paginasComQuebrados),
            'paginas_sem_links_entrada' => $paginasSemEntradaCollection->count(),
            'paginas_sem_links_saida' => $paginasSemSaidaCollection->count(),
            'profundidade_maxima' => $profundidadeMaxima,
            'estrutura' => [
                'diretorios_principais' => collect($diretoriosPrincipais)
                    ->take(12)
                    ->map(fn (int $total, string $diretorio) => [
                        'diretorio' => $diretorio,
                        'total' => $total,
                    ])
                    ->values()
                    ->all(),
                'distribuicao_profundidade' => collect($distribuicaoProfundidade)
                    ->map(fn (int $total, int|string $profundidade) => [
                        'profundidade' => (int) $profundidade,
                        'total' => $total,
                    ])
                    ->values()
                    ->all(),
                'paginas_mais_referenciadas' => $paginasMaisReferenciadas,
                'paginas_sem_links_entrada' => $paginasSemEntrada,
                'paginas_sem_links_saida' => $paginasSemSaida,
            ],
            'amostras' => [
                'links_quebrados' => collect($linksQuebrados)
                    ->take(50)
                    ->values()
                    ->all(),
                'links_externos' => collect($linksExternos)
                    ->sortByDesc('ocorrencias')
                    ->take(50)
                    ->values()
                    ->all(),
            ],
        ];
    }

    protected function relatorioVazio(): array
    {
        return [
            'disponivel' => false,
            'fonte' => null,
            'total_paginas' => 0,
            'total_links' => 0,
            'total_links_internos' => 0,
            'total_links_externos' => 0,
            'total_links_quebrados' => 0,
            'paginas_com_links_quebrados' => 0,
            'paginas_sem_links_entrada' => 0,
            'paginas_sem_links_saida' => 0,
            'profundidade_maxima' => 0,
            'estrutura' => [
                'diretorios_principais' => [],
                'distribuicao_profundidade' => [],
                'paginas_mais_referenciadas' => [],
                'paginas_sem_links_entrada' => [],
                'paginas_sem_links_saida' => [],
            ],
            'amostras' => [
                'links_quebrados' => [],
                'links_externos' => [],
            ],
        ];
    }

    protected function normalizarLinksSaida(string $sourceUrl, mixed $links, ?string $content): array
    {
        if (is_array($links) && !empty($links)) {
            return collect($links)
                ->map(function ($item) use ($sourceUrl) {
                    if (!is_array($item)) {
                        return null;
                    }

                    $targetUrl = $this->normalizarUrl($item['url'] ?? $item['href'] ?? null);

                    if (!$targetUrl) {
                        return null;
                    }

                    return [
                        'target_url' => $targetUrl,
                        'anchor_text' => $this->normalizarAnchor($item['anchor_text'] ?? null),
                        'is_external' => array_key_exists('is_external', $item)
                            ? (bool) $item['is_external']
                            : $this->isExternalLink($sourceUrl, $targetUrl),
                    ];
                })
                ->filter()
                ->unique(fn (array $item) => $item['target_url'] . '|' . ($item['anchor_text'] ?? '') . '|' . (int) $item['is_external'])
                ->values()
                ->all();
        }

        if (!is_string($content) || trim($content) === '') {
            return [];
        }

        return $this->extrairLinksDoHtml($sourceUrl, $content);
    }

    protected function extrairLinksDoHtml(string $sourceUrl, string $content): array
    {
        if (!class_exists(DOMDocument::class)) {
            return [];
        }

        $dom = new DOMDocument();
        $previousState = libxml_use_internal_errors(true);

        try {
            $dom->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        } catch (\Throwable) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
            return [];
        }

        $links = [];

        foreach ($dom->getElementsByTagName('a') as $node) {
            $href = trim((string) $node->getAttribute('href'));

            if ($href === '') {
                continue;
            }

            $targetUrl = $this->resolverUrlRelativa($sourceUrl, $href);

            if (!$targetUrl) {
                continue;
            }

            $links[] = [
                'target_url' => $targetUrl,
                'anchor_text' => $this->normalizarAnchor($node->textContent ?: null),
                'is_external' => $this->isExternalLink($sourceUrl, $targetUrl),
            ];
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        return collect($links)
            ->unique(fn (array $item) => $item['target_url'] . '|' . ($item['anchor_text'] ?? '') . '|' . (int) $item['is_external'])
            ->values()
            ->all();
    }

    protected function resolverUrlRelativa(string $baseUrl, string $href): ?string
    {
        $href = trim($href);

        if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:') || str_starts_with($href, 'javascript:')) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $href)) {
            return $this->normalizarUrl($href);
        }

        $base = parse_url($baseUrl);

        if (!$base || empty($base['scheme']) || empty($base['host'])) {
            return null;
        }

        $origin = strtolower($base['scheme']) . '://' . strtolower($base['host']);

        if (!empty($base['port'])) {
            $origin .= ':' . $base['port'];
        }

        if (str_starts_with($href, '//')) {
            return $this->normalizarUrl(strtolower($base['scheme']) . ':' . $href);
        }

        if (str_starts_with($href, '/')) {
            return $this->normalizarUrl($origin . $href);
        }

        $basePath = $base['path'] ?? '/';
        $directory = str_contains($basePath, '/')
            ? preg_replace('/\/[^\/]*$/', '/', $basePath)
            : '/';

        return $this->normalizarUrl($origin . ($directory ?: '/') . $href);
    }

    protected function normalizarAnchor(?string $anchor): ?string
    {
        $anchor = trim(preg_replace('/\s+/u', ' ', (string) $anchor));

        if ($anchor === '') {
            return null;
        }

        return mb_substr($anchor, 0, 255);
    }

    protected function isExternalLink(string $sourceUrl, string $targetUrl): bool
    {
        $sourceHost = $this->normalizarHost(parse_url($sourceUrl, PHP_URL_HOST));
        $targetHost = $this->normalizarHost(parse_url($targetUrl, PHP_URL_HOST));

        return $sourceHost !== '' && $targetHost !== '' && $sourceHost !== $targetHost;
    }

    protected function normalizarHost(?string $host): string
    {
        return strtolower(preg_replace('/^www\./i', '', trim((string) $host)));
    }

    protected function normalizarUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $partes = parse_url($url);

        if (!$partes || empty($partes['scheme']) || empty($partes['host'])) {
            return null;
        }

        $esquema = strtolower($partes['scheme']);
        $host = strtolower($partes['host']);
        $porta = isset($partes['port']) ? ':' . $partes['port'] : '';
        $caminho = $partes['path'] ?? '/';
        $query = isset($partes['query']) ? '?' . $partes['query'] : '';

        if ($caminho === '') {
            $caminho = '/';
        } elseif ($caminho !== '/') {
            $caminho = rtrim($caminho, '/');
        }

        return $esquema . '://' . $host . $porta . $caminho . $query;
    }

    protected function chaveUrl(string $url): string
    {
        return hash('sha256', $url);
    }

    protected function calcularProfundidade(string $url): int
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $segmentos = array_values(array_filter(explode('/', trim($path, '/'))));

        return count($segmentos);
    }

    protected function diretorioPrincipal(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $segmentos = array_values(array_filter(explode('/', trim($path, '/'))));

        if (empty($segmentos)) {
            return '/';
        }

        return '/' . $segmentos[0];
    }
}
