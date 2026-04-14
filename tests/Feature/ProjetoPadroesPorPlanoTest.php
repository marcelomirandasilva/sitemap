<?php

namespace Tests\Feature;

use App\Models\Plano;
use App\Models\Projeto;
use App\Models\User;
use App\Services\ExecucaoRastreamentoService;
use App\Services\SitemapGeneratorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjetoPadroesPorPlanoTest extends TestCase
{
    use RefreshDatabase;

    public function test_projeto_novo_herda_padroes_do_plano(): void
    {
        $plano = Plano::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'max_pages' => 1600000,
            'max_projects' => 100,
            'profundidade_maxima_padrao' => 10,
            'profundidade_maxima_limite' => 10,
            'concorrencia_padrao' => 30,
            'concorrencia_limite' => 100,
            'atraso_padrao_segundos' => 0.1,
            'atraso_minimo_segundos' => 0.1,
            'atraso_maximo_segundos' => 10,
            'intervalo_personalizado_padrao_horas' => 24,
            'has_advanced_features' => true,
            'permite_imagens' => true,
            'permite_videos' => true,
            'permite_noticias' => true,
            'permite_mobile' => true,
            'permite_compactacao' => true,
            'permite_cache_crawler' => true,
            'permite_padroes_exclusao' => true,
            'permite_politicas_crawl' => true,
            'update_frequency' => 'customizado',
        ]);

        $usuario = User::factory()->create([
            'plan_id' => $plano->id,
            'role' => 'admin',
        ]);

        $this
            ->actingAs($usuario)
            ->from(route('dashboard'))
            ->post(route('projects.store'), [
                'url' => 'https://www.iana.org',
            ])
            ->assertRedirect(route('dashboard'));

        $projeto = Projeto::query()->latest('id')->firstOrFail();

        $this->assertSame('customizado', $projeto->frequency);
        $this->assertSame(24, $projeto->intervalo_personalizado_horas);
        $this->assertSame(SitemapGeneratorService::LIMITE_MAXIMO_PAGINAS_API, $projeto->max_pages);
        $this->assertSame(10, $projeto->max_depth);
        $this->assertSame(30, $projeto->max_concurrent_requests);
        $this->assertSame(0.1, (float) $projeto->delay_between_requests);
        $this->assertFalse((bool) $projeto->check_news);
        $this->assertFalse((bool) $projeto->check_mobile);
    }

    public function test_execucao_normaliza_limite_de_paginas_de_projeto_antigo_para_o_teto_da_api(): void
    {
        Http::fake([
            'http://localhost:30000/api/v1/sitemaps' => Http::response([
                'job_id' => 'job-teste-123',
            ], 201),
        ]);

        $plano = Plano::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'max_pages' => 1600000,
            'max_projects' => 100,
            'profundidade_maxima_padrao' => 10,
            'profundidade_maxima_limite' => 10,
            'concorrencia_padrao' => 30,
            'concorrencia_limite' => 100,
            'atraso_padrao_segundos' => 0.1,
            'atraso_minimo_segundos' => 0.1,
            'atraso_maximo_segundos' => 10,
            'intervalo_personalizado_padrao_horas' => 24,
            'has_advanced_features' => true,
            'permite_imagens' => true,
            'permite_videos' => true,
            'permite_noticias' => true,
            'permite_mobile' => true,
            'permite_compactacao' => true,
            'permite_cache_crawler' => true,
            'permite_padroes_exclusao' => true,
            'permite_politicas_crawl' => true,
            'update_frequency' => 'customizado',
        ]);

        $usuario = User::factory()->create([
            'plan_id' => $plano->id,
            'role' => 'admin',
        ]);

        $projeto = Projeto::create([
            'user_id' => $usuario->id,
            'name' => 'Projeto legado',
            'url' => 'https://www.iana.org',
            'status' => 'pending',
            'frequency' => 'customizado',
            'intervalo_personalizado_horas' => 24,
            'max_pages' => 1600000,
            'max_depth' => 10,
            'max_concurrent_requests' => 30,
            'delay_between_requests' => 0.1,
            'check_images' => true,
            'check_videos' => true,
        ]);

        $resultado = app(ExecucaoRastreamentoService::class)->iniciar(
            $projeto,
            'Job criado e aguardando processamento.'
        );

        $this->assertTrue($resultado['success']);

        $projeto->refresh();

        $this->assertSame(SitemapGeneratorService::LIMITE_MAXIMO_PAGINAS_API, $projeto->max_pages);
        $this->assertSame(SitemapGeneratorService::LIMITE_MAXIMO_PAGINAS_API, $resultado['limite_paginas']);
    }
}
