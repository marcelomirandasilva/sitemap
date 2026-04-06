<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Services\CentralNotificacoesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificacoesTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_visualizar_notificacoes_no_historico(): void
    {
        $usuario = User::factory()->create();

        app(CentralNotificacoesService::class)->registrarNotificacao($usuario, [
            'titulo' => 'Crawler concluido',
            'mensagem' => 'Seu sitemap terminou com sucesso.',
            'url' => route('notifications.index'),
            'tipo' => 'crawler',
            'categoria' => 'crawler',
        ]);

        $response = $this
            ->actingAs($usuario)
            ->get(route('notifications.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Account/Notifications/Index')
            ->where('nao_lidas', 1)
            ->where('notificacoes.data.0.titulo', 'Crawler concluido')
            ->where('notificacoes.data.0.tipo', 'crawler')
        );
    }

    public function test_usuario_pode_marcar_todas_as_notificacoes_como_lidas(): void
    {
        $usuario = User::factory()->create();

        app(CentralNotificacoesService::class)->registrarNotificacao($usuario, [
            'titulo' => 'Teste 1',
            'mensagem' => 'Primeira notificacao',
            'tipo' => 'sistema',
        ]);

        app(CentralNotificacoesService::class)->registrarNotificacao($usuario, [
            'titulo' => 'Teste 2',
            'mensagem' => 'Segunda notificacao',
            'tipo' => 'sistema',
        ]);

        $response = $this
            ->actingAs($usuario)
            ->from(route('notifications.index'))
            ->post(route('notifications.read-all'));

        $response->assertRedirect(route('notifications.index'));
        $this->assertSame(0, $usuario->fresh()->unreadNotifications()->count());
    }
}
