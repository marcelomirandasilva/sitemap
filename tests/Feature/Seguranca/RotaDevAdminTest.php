<?php

namespace Tests\Feature\Seguranca;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RotaDevAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_comum_nao_acessa_rota_dev(): void
    {
        $usuario = User::factory()->create([
            'role' => 'user',
        ]);

        $this->actingAs($usuario)
            ->get(route('dev.api-test'))
            ->assertForbidden();
    }

    public function test_admin_acessa_rota_dev(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('dev.api-test'))
            ->assertOk();
    }
}
