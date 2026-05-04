<?php

namespace Tests\Unit;

use App\Models\Projeto;
use App\Services\RelatorioSeoProjetoService;
use PHPUnit\Framework\TestCase;

class RelatorioSeoProjetoServiceTest extends TestCase
{
    private string $arquivoStreamTemporario;

    protected function tearDown(): void
    {
        if (isset($this->arquivoStreamTemporario) && is_file($this->arquivoStreamTemporario)) {
            @unlink($this->arquivoStreamTemporario);
        }

        parent::tearDown();
    }

    public function test_monta_relatorio_seo_a_partir_do_stream_sem_precisar_materializar_todos_os_links(): void
    {
        $this->arquivoStreamTemporario = tempnam(sys_get_temp_dir(), 'seo-stream-');

        if ($this->arquivoStreamTemporario === false) {
            $this->fail('Nao foi possivel criar arquivo temporario para o teste.');
        }

        $this->escreverStreamCompactado($this->arquivoStreamTemporario, [
            [
                'url' => 'https://example.com/',
                'title' => 'Home',
                'status_code' => 200,
                'outgoing_links' => [
                    ['url' => 'https://example.com/curso', 'anchor_text' => 'Curso'],
                    ['url' => 'https://example.com/erro', 'anchor_text' => 'Link quebrado'],
                    ['url' => 'https://externo.com/promo', 'anchor_text' => 'Oferta'],
                ],
            ],
            [
                'url' => 'https://example.com/curso',
                'title' => 'Curso',
                'status_code' => 200,
                'outgoing_links' => [
                    ['url' => 'https://example.com/', 'anchor_text' => 'Voltar'],
                    ['url' => 'https://externo.com/promo', 'anchor_text' => 'Oferta'],
                ],
            ],
            [
                'url' => 'https://example.com/erro',
                'title' => 'Erro',
                'status_code' => 404,
                'outgoing_links' => [],
            ],
            [
                'url' => 'https://example.com/isolada',
                'title' => 'Isolada',
                'status_code' => 200,
                'outgoing_links' => [],
            ],
        ]);

        $service = new class($this->arquivoStreamTemporario) extends RelatorioSeoProjetoService
        {
            public function __construct(private readonly string $caminhoStream)
            {
            }

            protected function encontrarPagesStream(Projeto $projeto): ?string
            {
                return $this->caminhoStream;
            }
        };

        $relatorio = $service->montarParaProjeto(new Projeto(['id' => 999]));

        $this->assertTrue($relatorio['disponivel']);
        $this->assertSame('stream', $relatorio['fonte']);
        $this->assertSame(4, $relatorio['total_paginas']);
        $this->assertSame(5, $relatorio['total_links']);
        $this->assertSame(3, $relatorio['total_links_internos']);
        $this->assertSame(2, $relatorio['total_links_externos']);
        $this->assertSame(1, $relatorio['total_links_quebrados']);
        $this->assertSame(1, $relatorio['paginas_com_links_quebrados']);
        $this->assertSame(1, $relatorio['paginas_sem_links_entrada']);
        $this->assertSame(2, $relatorio['paginas_sem_links_saida']);
        $this->assertSame(1, $relatorio['profundidade_maxima']);

        $paginasMaisReferenciadas = collect($relatorio['estrutura']['paginas_mais_referenciadas'])->keyBy('url');

        $this->assertSame(1, $paginasMaisReferenciadas['https://example.com/curso']['total']);
        $this->assertSame(1, $paginasMaisReferenciadas['https://example.com/']['total']);
        $this->assertSame('https://example.com/isolada', $relatorio['estrutura']['paginas_sem_links_entrada'][0]['url']);
        $this->assertSame('https://example.com/erro', $relatorio['estrutura']['paginas_sem_links_saida'][0]['url']);
        $this->assertSame('https://example.com/isolada', $relatorio['estrutura']['paginas_sem_links_saida'][1]['url']);

        $this->assertCount(1, $relatorio['amostras']['links_quebrados']);
        $this->assertSame('https://example.com/erro', $relatorio['amostras']['links_quebrados'][0]['target_url']);
        $this->assertCount(1, $relatorio['amostras']['links_externos']);
        $this->assertSame('https://externo.com/promo', $relatorio['amostras']['links_externos'][0]['target_url']);
        $this->assertSame(2, $relatorio['amostras']['links_externos'][0]['ocorrencias']);
    }

    private function escreverStreamCompactado(string $arquivo, array $paginas): void
    {
        $handle = gzopen($arquivo, 'w9');

        if ($handle === false) {
            $this->fail('Nao foi possivel abrir o arquivo temporario compactado.');
        }

        foreach ($paginas as $pagina) {
            gzwrite($handle, json_encode($pagina, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        }

        gzclose($handle);
    }
}
