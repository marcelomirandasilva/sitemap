<?php

namespace Tests\Feature\Assinatura;

use App\Models\User;
use App\Services\SincronizacaoAssinaturaStripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CheckoutSuccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_retorno_do_checkout_sincroniza_por_session_id(): void
    {
        $usuario = User::factory()->create();

        $servico = Mockery::mock(SincronizacaoAssinaturaStripeService::class);
        $servico->shouldReceive('sincronizarPorSessaoCheckout')
            ->once()
            ->withArgs(function ($usuarioRecebido, $idSessao) use ($usuario) {
                return $usuarioRecebido->is($usuario) && $idSessao === 'cs_test_123';
            })
            ->andReturn(true);

        $this->app->instance(SincronizacaoAssinaturaStripeService::class, $servico);

        $this->actingAs($usuario)
            ->get(route('subscription.checkout.success', ['session_id' => 'cs_test_123']))
            ->assertRedirect(route('dashboard', ['checkout' => 'success']));
    }

    public function test_retorno_do_checkout_usa_fallback_quando_nao_recebe_session_id(): void
    {
        $usuario = User::factory()->create();

        $servico = Mockery::mock(SincronizacaoAssinaturaStripeService::class);
        $servico->shouldReceive('sincronizarAssinaturaAtivaMaisRecente')
            ->once()
            ->withArgs(fn ($usuarioRecebido) => $usuarioRecebido->is($usuario))
            ->andReturn(true);

        $this->app->instance(SincronizacaoAssinaturaStripeService::class, $servico);

        $this->actingAs($usuario)
            ->get(route('subscription.checkout.success'))
            ->assertRedirect(route('dashboard', ['checkout' => 'success']));
    }

    public function test_retorno_do_checkout_redireciona_com_erro_quando_nao_consegue_sincronizar(): void
    {
        $usuario = User::factory()->create();

        $servico = Mockery::mock(SincronizacaoAssinaturaStripeService::class);
        $servico->shouldReceive('sincronizarPorSessaoCheckout')
            ->once()
            ->andReturn(false);

        $this->app->instance(SincronizacaoAssinaturaStripeService::class, $servico);

        $this->actingAs($usuario)
            ->get(route('subscription.checkout.success', ['session_id' => 'cs_test_erro']))
            ->assertRedirect(route('subscription.index'));
    }

    public function test_dashboard_com_checkout_success_reconcilia_assinatura_antiga(): void
    {
        $usuario = User::factory()->create();

        $servico = Mockery::mock(SincronizacaoAssinaturaStripeService::class);
        $servico->shouldReceive('sincronizarAssinaturaAtivaMaisRecente')
            ->once()
            ->withArgs(fn ($usuarioRecebido) => $usuarioRecebido->is($usuario))
            ->andReturn(true);

        $this->app->instance(SincronizacaoAssinaturaStripeService::class, $servico);

        $this->actingAs($usuario)
            ->get(route('dashboard', ['checkout' => 'success']))
            ->assertRedirect(route('dashboard'));
    }
}
