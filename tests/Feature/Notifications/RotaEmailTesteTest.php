<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\EmailSistema;
use App\Notifications\RedefinirSenha;
use App\Notifications\SenhaAlterada;
use App\Notifications\WelcomeAndVerifyUser;
use App\Notifications\WelcomeNewUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RotaEmailTesteTest extends TestCase
{
    use RefreshDatabase;

    public function test_rota_de_email_teste_envia_todos_os_modelos_para_usuario_autenticado(): void
    {
        Notification::fake();

        $usuario = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($usuario)->get(route('dev.email-test'));

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'email_destino' => $usuario->email,
                'quantidade' => 8,
            ]);

        Notification::assertSentTo($usuario, WelcomeAndVerifyUser::class);
        Notification::assertSentTo($usuario, WelcomeNewUser::class);
        Notification::assertSentTo($usuario, RedefinirSenha::class);
        Notification::assertSentTo($usuario, SenhaAlterada::class);
        Notification::assertSentToTimes($usuario, EmailSistema::class, 4);
    }

    public function test_rota_de_email_teste_pode_enviar_um_tipo_especifico(): void
    {
        Notification::fake();

        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get(route('dev.email-test', [
            'tipo' => 'plano',
        ]));

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'quantidade' => 1,
                'tipos' => ['plano'],
            ]);

        Notification::assertSentToTimes($usuario, EmailSistema::class, 1);
        Notification::assertCount(1);
    }

    public function test_rota_de_email_teste_rejeita_tipo_invalido(): void
    {
        Notification::fake();

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('dev.email-test', ['tipo' => 'inexistente']))
            ->assertStatus(422);
    }
}
