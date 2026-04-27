<?php

namespace App\Http\Controllers;

use App\Models\Plano;
use App\Services\RastreabilidadeStripeService;
use App\Services\SincronizacaoAssinaturaStripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Cashier\Exceptions\IncompletePayment;

class AssinaturaController extends Controller
{
    public function __construct(
        private SincronizacaoAssinaturaStripeService $sincronizacaoAssinaturaStripe,
        private RastreabilidadeStripeService $rastreabilidadeStripe
    )
    {
    }

    /**
     * Exibe a tela de planos.
     */
    public function index()
    {
        $usuario = auth()->user()->fresh();
        $assinatura = $usuario->subscription('default');
        $planoAtual = $usuario->planoEfetivo();

        return Inertia::render('Assinatura/Index', [
            'planos' => Plano::orderBy('price_monthly_brl', 'asc')->get(),
            'assinatura_atual' => $assinatura,
            'id_preco_atual' => $assinatura ? $assinatura->stripe_price : null,
            'id_plano_atual' => $planoAtual?->id,
            'slug_plano_atual' => $planoAtual?->slug,
            'esta_cancelado' => $assinatura ? $assinatura->canceled() : false,
            'em_periodo_carencia' => $assinatura ? $assinatura->onGracePeriod() : false,
            'termina_em' => $assinatura && $assinatura->ends_at ? $assinatura->ends_at->format('d/m/Y') : null,
            'cartao_final_4' => $usuario->pm_last_four,
            'marca_do_cartao' => $usuario->pm_type,
        ]);
    }

    /**
     * Processa o checkout ou a troca de plano.
     */
    public function checkout(Request $request, string $id_preco)
    {
        $usuario = $request->user();

        if ($id_preco === 'free' && !$usuario->subscribed('default')) {
            return redirect()->route('subscription.index');
        }

        if ($usuario->subscribed('default')) {
            $plano = Plano::where('stripe_monthly_price_id', $id_preco)
                ->orWhere('stripe_yearly_price_id', $id_preco)
                ->first();

            if (!$plano || (!$plano->stripe_monthly_price_id && !$plano->stripe_yearly_price_id)) {
                $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
                    'user_id' => $usuario->id,
                    'plano_origem_id' => $usuario->plan_id,
                    'plano_destino_id' => $usuario->planoGratuito()?->id,
                    'origem' => 'aplicacao',
                    'tipo_movimentacao' => 'cancelamento_solicitado',
                    'status' => 'solicitado',
                    'stripe_customer_id' => $usuario->stripe_id,
                    'stripe_subscription_id' => $usuario->subscription('default')?->stripe_id,
                    'stripe_price_id' => $usuario->subscription('default')?->stripe_price,
                    'descricao' => 'Usuario solicitou cancelamento da assinatura paga pela tela de planos.',
                    'dados' => ['id_preco_solicitado' => $id_preco],
                ]);

                $usuario->subscription('default')->cancel();

                return redirect()->route('subscription.index')
                    ->with('success', 'Sua assinatura paga foi cancelada. Voce continuara com os recursos atuais ate o fim do periodo faturado e depois voltara ao plano gratuito.');
            }

            try {
                $planoAnteriorId = $usuario->plan_id;

                $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
                    'user_id' => $usuario->id,
                    'plano_origem_id' => $planoAnteriorId,
                    'plano_destino_id' => $plano->id,
                    'origem' => 'aplicacao',
                    'tipo_movimentacao' => 'troca_plano_solicitada',
                    'status' => 'processando',
                    'stripe_customer_id' => $usuario->stripe_id,
                    'stripe_subscription_id' => $usuario->subscription('default')?->stripe_id,
                    'stripe_price_id' => $id_preco,
                    'descricao' => "Usuario solicitou troca de plano para {$plano->name}.",
                    'dados' => ['id_preco_solicitado' => $id_preco],
                ]);

                $usuario->subscription('default')->swapAndInvoice($id_preco);
                try {
                    $sincronizado = $this->sincronizacaoAssinaturaStripe->sincronizarAssinaturaAtivaMaisRecente($usuario->fresh());

                    if (!$sincronizado) {
                        return redirect()->route('subscription.index')
                            ->with('warning', 'O pagamento foi processado, mas o plano ainda esta sendo sincronizado. Atualize a tela em alguns instantes.');
                    }
                } catch (\Throwable $erroSincronizacao) {
                    report($erroSincronizacao);

                    return redirect()->route('subscription.index')
                        ->with('warning', 'O pagamento foi processado, mas ainda nao foi possivel confirmar o novo plano localmente. Atualize a tela em alguns instantes.');
                }

                $usuarioAtualizado = $usuario->fresh();
                $planoAtualizado = $usuarioAtualizado->planoEfetivo(false);
                $assinaturaAtualizada = $usuarioAtualizado->subscription('default');

                $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
                    'user_id' => $usuarioAtualizado->id,
                    'plano_origem_id' => $planoAnteriorId,
                    'plano_destino_id' => $planoAtualizado?->id,
                    'origem' => 'aplicacao',
                    'tipo_movimentacao' => 'troca_plano_confirmada_localmente',
                    'status' => $assinaturaAtualizada?->stripe_status,
                    'stripe_customer_id' => $usuarioAtualizado->stripe_id,
                    'stripe_subscription_id' => $assinaturaAtualizada?->stripe_id,
                    'stripe_price_id' => $assinaturaAtualizada?->stripe_price,
                    'descricao' => 'Plano reconciliado localmente apos swapAndInvoice.',
                    'dados' => ['id_preco_solicitado' => $id_preco],
                ]);

                return redirect()->route('subscription.index')
                    ->with('success', 'Plano atualizado com sucesso! O acesso foi liberado.');
            } catch (IncompletePayment $excecao) {
                return Inertia::location(route(
                    'cashier.payment',
                    [$excecao->payment->id, 'redirect' => route('subscription.index')]
                ));
            } catch (\Throwable $erro) {
                $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
                    'user_id' => $usuario->id,
                    'plano_origem_id' => $usuario->plan_id,
                    'plano_destino_id' => $plano->id,
                    'origem' => 'aplicacao',
                    'tipo_movimentacao' => 'troca_plano_falhou',
                    'status' => 'erro',
                    'stripe_customer_id' => $usuario->stripe_id,
                    'stripe_subscription_id' => $usuario->subscription('default')?->stripe_id,
                    'stripe_price_id' => $id_preco,
                    'descricao' => 'Falha local ao solicitar troca de plano.',
                    'dados' => ['mensagem_erro' => $erro->getMessage()],
                ]);

                report($erro);
                session()->flash('error', 'Houve um problema no pagamento automatico. Por favor, verifique seu cartao.');

                return Inertia::location(route('subscription.portal'));
            }
        }

        $plano = Plano::where('stripe_monthly_price_id', $id_preco)
            ->orWhere('stripe_yearly_price_id', $id_preco)
            ->first();

        $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
            'user_id' => $usuario->id,
            'plano_origem_id' => $usuario->plan_id,
            'plano_destino_id' => $plano?->id,
            'origem' => 'aplicacao',
            'tipo_movimentacao' => 'checkout_iniciado',
            'status' => 'redirecionado',
            'stripe_customer_id' => $usuario->stripe_id,
            'stripe_price_id' => $id_preco,
            'descricao' => 'Usuario foi redirecionado ao checkout hospedado da Stripe.',
            'dados' => ['id_preco_solicitado' => $id_preco],
        ]);

        $checkout = $usuario->newSubscription('default', $id_preco)
            ->checkout([
                'success_url' => route('subscription.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscription.index') . '?checkout=cancel',
                'client_reference_id' => (string) $usuario->id,
            ]);

        return Inertia::location($checkout->url);
    }

    /**
     * Trata o retorno do checkout do Stripe e reconcilia a assinatura localmente.
     */
    public function sucessoCheckout(Request $request)
    {
        $usuario = $request->user();
        $idSessaoCheckout = trim((string) $request->query('session_id', ''));

        try {
            $sincronizado = $idSessaoCheckout !== ''
                ? $this->sincronizacaoAssinaturaStripe->sincronizarPorSessaoCheckout($usuario, $idSessaoCheckout)
                : $this->sincronizacaoAssinaturaStripe->sincronizarAssinaturaAtivaMaisRecente($usuario);

            if ($sincronizado) {
                $usuarioAtualizado = $usuario->fresh();
                $planoAtualizado = $usuarioAtualizado->planoEfetivo(false);
                $assinaturaAtualizada = $usuarioAtualizado->subscription('default');

                $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
                    'user_id' => $usuarioAtualizado->id,
                    'plano_origem_id' => $usuario->plan_id,
                    'plano_destino_id' => $planoAtualizado?->id,
                    'origem' => 'aplicacao',
                    'tipo_movimentacao' => 'checkout_sincronizado',
                    'status' => $assinaturaAtualizada?->stripe_status,
                    'stripe_customer_id' => $usuarioAtualizado->stripe_id,
                    'stripe_subscription_id' => $assinaturaAtualizada?->stripe_id,
                    'stripe_price_id' => $assinaturaAtualizada?->stripe_price,
                    'descricao' => 'Checkout concluido e assinatura sincronizada localmente.',
                    'dados' => ['session_id' => $idSessaoCheckout],
                ]);

                return redirect()
                    ->route('dashboard', ['checkout' => 'success'])
                    ->with('success', 'Assinatura confirmada e plano atualizado com sucesso.');
            }
        } catch (\Throwable $erro) {
            $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
                'user_id' => $usuario->id,
                'plano_origem_id' => $usuario->plan_id,
                'plano_destino_id' => null,
                'origem' => 'aplicacao',
                'tipo_movimentacao' => 'checkout_sincronizacao_falhou',
                'status' => 'erro',
                'stripe_customer_id' => $usuario->stripe_id,
                'descricao' => 'Checkout foi concluido na Stripe, mas a sincronizacao local falhou.',
                'dados' => [
                    'session_id' => $idSessaoCheckout,
                    'mensagem_erro' => $erro->getMessage(),
                ],
            ]);

            report($erro);
        }

        return redirect()
            ->route('subscription.index')
            ->with('error', 'O pagamento foi concluido, mas ainda nao foi possivel sincronizar o plano localmente. Tente atualizar em alguns instantes ou contate o suporte.');
    }

    /**
     * Portal do cliente para assinatura e pagamento.
     */
    public function portal(Request $request)
    {
        $url = $request->user()->billingPortalUrl(route('subscription.index'));

        return Inertia::location($url);
    }
}
