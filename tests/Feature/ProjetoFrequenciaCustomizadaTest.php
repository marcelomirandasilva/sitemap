<?php

namespace Tests\Feature;

use App\Models\Plano;
use App\Models\Projeto;
use App\Models\User;
use App\Services\SitemapGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjetoFrequenciaCustomizadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_com_plano_customizado_pode_salvar_intervalo_personalizado(): void
    {
        $plano = Plano::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'max_pages' => SitemapGeneratorService::LIMITE_MAXIMO_PAGINAS_API,
            'max_projects' => 100,
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
            'name' => 'Projeto enterprise',
            'url' => 'https://exemplo.com',
            'status' => 'active',
            'frequency' => 'manual',
            'max_pages' => 1000,
        ]);

        $response = $this
            ->actingAs($usuario)
            ->from(route('projects.show', $projeto))
            ->patch(route('projects.update', $projeto), [
                'frequency' => 'customizado',
                'intervalo_personalizado_horas' => 12,
                'max_pages' => 1000,
            ]);

        $response->assertRedirect(route('projects.show', $projeto));

        $this->assertDatabaseHas('projects', [
            'id' => $projeto->id,
            'frequency' => 'customizado',
            'intervalo_personalizado_horas' => 12,
        ]);

        $projeto->refresh();

        $this->assertNotNull($projeto->next_scheduled_crawl_at);
    }
}
