<?php

namespace Tests\Feature\Seguranca;

use App\Models\Plano;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidacaoUrlExternaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_nao_pode_salvar_callback_url_interna(): void
    {
        $plano = Plano::create([
            'name' => 'Free',
            'slug' => 'free',
            'max_pages' => 500,
            'has_advanced_features' => true,
            'max_projects' => 1,
        ]);

        $usuario = User::factory()->create([
            'plan_id' => $plano->id,
        ]);

        $response = $this
            ->actingAs($usuario)
            ->postJson(route('account.api.callback-url'), [
                'callback_url' => 'http://127.0.0.1:8080/webhook',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['callback_url']);
    }

    public function test_usuario_nao_pode_validar_sitemap_publico_interno(): void
    {
        $usuario = User::factory()->create();

        $projeto = Projeto::create([
            'user_id' => $usuario->id,
            'name' => 'Site teste',
            'url' => 'https://exemplo.com',
            'status' => 'active',
            'frequency' => 'manual',
        ]);

        $response = $this
            ->actingAs($usuario)
            ->postJson(route('projects.search-engines.google.submit', $projeto), [
                'site_property' => 'sc-domain:exemplo.com',
                'published_sitemap_url' => 'http://localhost/sitemap.xml',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['published_sitemap_url']);
    }
}
