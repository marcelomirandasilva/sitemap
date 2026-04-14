<?php

namespace Tests\Feature;

use App\Models\Plano;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjetoLimitePorPlanoTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_nao_pode_criar_mais_projetos_do_que_o_limite_do_plano(): void
    {
        $plano = Plano::create([
            'name' => 'Solo',
            'slug' => 'solo',
            'max_pages' => 1600,
            'max_projects' => 1,
            'has_advanced_features' => false,
        ]);

        $usuario = User::factory()->create([
            'plan_id' => $plano->id,
        ]);

        Projeto::create([
            'user_id' => $usuario->id,
            'name' => 'Projeto existente',
            'url' => 'https://exemplo.com',
            'status' => 'active',
            'frequency' => 'manual',
        ]);

        $response = $this
            ->actingAs($usuario)
            ->from(route('dashboard'))
            ->post(route('projects.store'), [
                'url' => 'https://www.iana.org',
            ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['url']);

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_usuario_pode_criar_projeto_quando_ha_capacidade_no_plano(): void
    {
        $plano = Plano::create([
            'name' => 'Solo',
            'slug' => 'solo',
            'max_pages' => 1600,
            'max_projects' => 3,
            'has_advanced_features' => false,
        ]);

        $usuario = User::factory()->create([
            'plan_id' => $plano->id,
        ]);

        $response = $this
            ->actingAs($usuario)
            ->from(route('dashboard'))
            ->post(route('projects.store'), [
                'url' => 'https://www.iana.org',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('projects', [
            'user_id' => $usuario->id,
            'url' => 'https://www.iana.org',
        ]);
    }
}
