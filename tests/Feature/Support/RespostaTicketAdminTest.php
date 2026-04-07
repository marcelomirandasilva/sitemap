<?php

namespace Tests\Feature\Support;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RespostaTicketAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_resposta_do_admin_cria_notificacao_para_o_usuario(): void
    {
        $usuario = User::factory()->create();
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $ticket = Ticket::create([
            'user_id' => $usuario->id,
            'titulo' => 'Ajuda com sitemap',
            'mensagem' => 'Preciso de suporte',
            'status' => Ticket::STATUS_ABERTO,
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.tickets.show', $ticket))
            ->post(route('admin.tickets.reply', $ticket), [
                'mensagem' => 'Ja analisamos e corrigimos.',
            ]);

        $response->assertRedirect(route('admin.tickets.show', $ticket));

        $ticket->refresh();

        $this->assertSame(Ticket::STATUS_RESPONDIDO, $ticket->status);
        $this->assertDatabaseHas('respostas_tickets', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'is_admin' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $usuario->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertSame('Nova resposta da equipe', $usuario->fresh()->notifications()->latest()->first()->data['titulo']);
    }

    public function test_resposta_do_admin_respeita_preferencia_de_notificacao_do_usuario(): void
    {
        $usuario = User::factory()->create([
            'notification_preferences' => [
                'support_updates' => false,
            ],
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $ticket = Ticket::create([
            'user_id' => $usuario->id,
            'titulo' => 'Outro ticket',
            'mensagem' => 'Mensagem inicial',
            'status' => Ticket::STATUS_ABERTO,
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.tickets.show', $ticket))
            ->post(route('admin.tickets.reply', $ticket), [
                'mensagem' => 'Resposta administrativa.',
            ])
            ->assertRedirect(route('admin.tickets.show', $ticket));

        $this->assertSame(0, $usuario->fresh()->notifications()->count());
    }
}
