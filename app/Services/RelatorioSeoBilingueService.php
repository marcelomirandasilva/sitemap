<?php

namespace App\Services;

use App\Models\Projeto;
use Illuminate\Support\Collection;

class RelatorioSeoBilingueService
{
    public function montarParaProjeto(Projeto $projeto): array
    {
        $paginas = $this->carregarPaginas($projeto);

        if ($paginas->isEmpty()) {
            return $this->relatorioVazio();
        }

        $idiomasDetectados = $paginas
            ->map(fn ($pagina) => $this->normalizarIdioma($pagina['language'] ?? null))
            ->filter()
            ->countBy()
            ->sortKeys();

        $registros = $paginas->map(function (array $pagina) {
            $urlAtual = $this->normalizarUrl($pagina['url'] ?? null);
            $urlCanonica = $this->normalizarUrl($pagina['canonical_url'] ?? null);
            $linksHreflang = $this->normalizarLinksHreflang($pagina['hreflang_links'] ?? []);
            $temHreflang = $linksHreflang->isNotEmpty();
            $urlAutorreferencia = $urlCanonica ?: $urlAtual;

            return [
                'id' => $pagina['id'] ?? null,
                'url' => $pagina['url'] ?? null,
                'title' => $pagina['title'] ?? null,
                'status_code' => $pagina['status_code'] ?? null,
                'language' => $this->normalizarIdioma($pagina['language'] ?? null),
                'canonical_url' => $pagina['canonical_url'] ?? null,
                'canonical_normalizada' => $urlCanonica,
                'hreflang_links' => $linksHreflang->values()->all(),
                'total_hreflang' => $linksHreflang->count(),
                'tem_hreflang' => $temHreflang,
                'tem_canonical' => !empty($urlCanonica),
                'tem_x_default' => $linksHreflang->contains(fn (array $item) => $item['lang'] === 'x-default'),
                'tem_autorreferencia' => $temHreflang && $linksHreflang->contains(function (array $item) use ($urlAtual, $urlAutorreferencia) {
                    return $item['url'] === $urlAtual || $item['url'] === $urlAutorreferencia;
                }),
            ];
        });

        $siteMultilingue = $idiomasDetectados->count() > 1 || $registros->contains(fn (array $registro) => $registro['tem_hreflang']);

        $paginasSemCanonical = $registros
            ->filter(fn (array $registro) => !$registro['tem_canonical'])
            ->values();

        $paginasSemAutorreferencia = $registros
            ->filter(fn (array $registro) => $registro['tem_hreflang'] && !$registro['tem_autorreferencia'])
            ->values();

        $paginasSemHreflang = $siteMultilingue
            ? $registros->filter(fn (array $registro) => !$registro['tem_hreflang'])->values()
            : collect();

        return [
            'disponivel' => true,
            'site_multilingue' => $siteMultilingue,
            'total_paginas' => $registros->count(),
            'idiomas_detectados' => $idiomasDetectados
                ->map(fn (int $total, string $codigo) => [
                    'codigo' => $codigo,
                    'total' => $total,
                ])
                ->values()
                ->all(),
            'paginas_com_hreflang' => $registros->where('tem_hreflang', true)->count(),
            'paginas_sem_hreflang' => $paginasSemHreflang->count(),
            'paginas_sem_canonical' => $paginasSemCanonical->count(),
            'paginas_sem_autorreferencia' => $paginasSemAutorreferencia->count(),
            'paginas_com_x_default' => $registros->where('tem_x_default', true)->count(),
            'amostras' => [
                'sem_canonical' => $this->montarAmostras($paginasSemCanonical),
                'sem_hreflang' => $this->montarAmostras($paginasSemHreflang),
                'sem_autorreferencia' => $this->montarAmostras($paginasSemAutorreferencia),
            ],
        ];
    }

    protected function carregarPaginas(Projeto $projeto): Collection
    {
        $paginasDoBanco = $projeto->paginas()
            ->select([
                'id',
                'url',
                'title',
                'status_code',
                'language',
                'canonical_url',
                'hreflang_links',
            ])
            ->orderBy('id')
            ->get()
            ->map(fn ($pagina) => [
                'id' => $pagina->id,
                'url' => $pagina->url,
                'title' => $pagina->title,
                'status_code' => $pagina->status_code,
                'language' => $pagina->language,
                'canonical_url' => $pagina->canonical_url,
                'hreflang_links' => $pagina->hreflang_links ?? [],
            ]);

        if ($paginasDoBanco->isEmpty()) {
            return collect();
        }

        $temSinaisBilingues = $paginasDoBanco->contains(function (array $pagina) {
            return !empty($pagina['language']) || !empty($pagina['canonical_url']) || !empty($pagina['hreflang_links']);
        });

        if ($temSinaisBilingues) {
            return $paginasDoBanco;
        }

        $paginasDoStream = $this->carregarPaginasDoStream($projeto);

        return $paginasDoStream->isNotEmpty() ? $paginasDoStream : $paginasDoBanco;
    }

    protected function relatorioVazio(): array
    {
        return [
            'disponivel' => false,
            'site_multilingue' => false,
            'total_paginas' => 0,
            'idiomas_detectados' => [],
            'paginas_com_hreflang' => 0,
            'paginas_sem_hreflang' => 0,
            'paginas_sem_canonical' => 0,
            'paginas_sem_autorreferencia' => 0,
            'paginas_com_x_default' => 0,
            'amostras' => [
                'sem_canonical' => [],
                'sem_hreflang' => [],
                'sem_autorreferencia' => [],
            ],
        ];
    }

    protected function montarAmostras(Collection $registros): array
    {
        return $registros
            ->take(12)
            ->map(fn (array $registro) => [
                'url' => $registro['url'],
                'title' => $registro['title'],
                'language' => $registro['language'],
                'canonical_url' => $registro['canonical_url'],
                'total_hreflang' => $registro['total_hreflang'],
                'hreflang_links' => $registro['hreflang_links'],
            ])
            ->values()
            ->all();
    }

    protected function normalizarLinksHreflang(mixed $links): Collection
    {
        if (!is_array($links)) {
            return collect();
        }

        return collect($links)
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                $idioma = $this->normalizarIdioma($item['lang'] ?? $item['hreflang'] ?? null, true);
                $url = $this->normalizarUrl($item['url'] ?? $item['href'] ?? null);

                if (!$idioma || !$url) {
                    return null;
                }

                return [
                    'lang' => $idioma,
                    'url' => $url,
                ];
            })
            ->filter()
            ->unique(fn (array $item) => $item['lang'] . '|' . $item['url']);
    }

    protected function carregarPaginasDoStream(Projeto $projeto): Collection
    {
        $ultimoJob = $projeto->tarefasSitemap()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        if (!$ultimoJob) {
            return collect();
        }

        $caminhos = [
            base_path('../api-sitemap/sitemaps/projects/' . $projeto->id . '/streams/pages_stream.jsonl.gz'),
            base_path('../api-sitemap/sitemaps/' . $ultimoJob->external_job_id . '/streams/pages_stream.jsonl.gz'),
        ];

        $caminhoValido = collect($caminhos)->first(fn ($caminho) => is_string($caminho) && is_file($caminho));

        if (!$caminhoValido) {
            return collect();
        }

        $paginas = [];
        $handle = @gzopen($caminhoValido, 'r');

        if (!$handle) {
            return collect();
        }

        while (($linha = gzgets($handle)) !== false) {
            $dados = json_decode($linha, true);

            if (!is_array($dados) || empty($dados['url'])) {
                continue;
            }

            $paginas[] = [
                'id' => null,
                'url' => $dados['url'],
                'title' => $dados['title'] ?? null,
                'status_code' => $dados['status_code'] ?? null,
                'language' => $dados['language'] ?? null,
                'canonical_url' => $dados['canonical_url'] ?? null,
                'hreflang_links' => $dados['hreflang_links'] ?? [],
            ];
        }

        gzclose($handle);

        return collect($paginas);
    }

    protected function normalizarIdioma(?string $idioma, bool $preservarXDefault = false): ?string
    {
        $idioma = trim(mb_strtolower(str_replace('_', '-', (string) $idioma)));

        if ($idioma === '') {
            return null;
        }

        if ($preservarXDefault && $idioma === 'x-default') {
            return $idioma;
        }

        return substr($idioma, 0, 20);
    }

    protected function normalizarUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $partes = parse_url($url);

        if (!$partes || empty($partes['scheme']) || empty($partes['host'])) {
            return rtrim($url, '/');
        }

        $esquema = strtolower($partes['scheme']);
        $host = strtolower($partes['host']);
        $porta = isset($partes['port']) ? ':' . $partes['port'] : '';
        $caminho = $partes['path'] ?? '';
        $query = isset($partes['query']) ? '?' . $partes['query'] : '';

        if ($caminho === '' || $caminho === '/') {
            $caminho = '/';
        } else {
            $caminho = rtrim($caminho, '/');
        }

        return $esquema . '://' . $host . $porta . $caminho . $query;
    }
}
