<?php

namespace Tests\Feature\Support;

use App\Models\Projeto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketProjetoOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_nao_pode_criar_ticket_vinculado_a_projeto_de_outro_usuario(): void
    {
        $usuario = User::factory()->create();
        $outroUsuario = User::factory()->create();

        $projetoDoOutro = Projeto::create([
            'user_id' => $outroUsuario->id,
            'name' => 'Projeto externo',
            'url' => 'https://externo.com',
            'status' => 'active',
            'frequency' => 'manual',
        ]);

        $response = $this
            ->actingAs($usuario)
            ->from(route('support.index'))
            ->post(route('support.store'), [
                'titulo' => 'Preciso de ajuda',
                'projeto_id' => $projetoDoOutro->id,
                'mensagem' => 'Mensagem com tamanho suficiente para validar.',
            ]);

        $response
            ->assertRedirect(route('support.index'))
            ->assertSessionHasErrors(['projeto_id']);

        $this->assertDatabaseMissing('tickets', [
            'user_id' => $usuario->id,
            'projeto_id' => $projetoDoOutro->id,
            'titulo' => 'Preciso de ajuda',
        ]);
    }
}
