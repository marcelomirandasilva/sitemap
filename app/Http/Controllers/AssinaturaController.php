<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Plano;
use Laravel\Cashier\Exceptions\IncompletePayment;

class AssinaturaController extends Controller
{
    /**
     * Exibe a tela de planos
     */
    public function index()
    {
        $usuario = auth()->user();
        $assinatura = $usuario->subscription('default');
        $planoAtual = $usuario->planoEfetivo();

        return Inertia::render('Assinatura/Index', [
            // Trazendo planos ordenados pelo preço
            'planos' => Plano::orderBy('price_monthly_brl', 'asc')->get(),

            // Dados da assinatura atual
            'assinatura_atual' => $assinatura,
            'id_preco_atual' => $assinatura ? $assinatura->stripe_price : null,
            'id_plano_atual' => $planoAtual?->id,
            'slug_plano_atual' => $planoAtual?->slug,
            'esta_cancelado' => $assinatura ? $assinatura->canceled() : false,
            'em_periodo_carencia' => $assinatura ? $assinatura->onGracePeriod() : false,
            'termina_em' => $assinatura && $assinatura->ends_at ? $assinatura->ends_at->format('d/m/Y') : null,

            // Dados parciais do cartão para a mensagem de confirmação
            'cartao_final_4' => $usuario->pm_last_four,
            'marca_do_cartao' => $usuario->pm_type,
        ]);
    }

    /**
     * Processa o Checkout ou a Troca de Plano (Swap)
     */
    public function checkout(Request $request, string $id_preco)
    {
        $usuario = $request->user();

        // ----------------------------------------------------------------------
        // CENÁRIO 1: Usuário já é assinante (UPGRADE/DOWNGRADE)
        // ----------------------------------------------------------------------
        if ($usuario->subscribed('default')) {
            // Se o plano escolhido for o "Free" (identificado por não ter price_id no Stripe)
            $plano = Plano::where('stripe_monthly_price_id', $id_preco)
                ->orWhere('stripe_yearly_price_id', $id_preco)
                ->first();

            if (!$plano || (!$plano->stripe_monthly_price_id && !$plano->stripe_yearly_price_id)) {
                // Usuário quer voltar ao FREE -> Cancela a assinatura atual
                $usuario->subscription('default')->cancel();
                return redirect()->route('subscription.index')
                    ->with('success', 'Sua assinatura paga foi cancelada. Você continuará com os recursos atuais até o fim do período faturado e depois voltará ao plano gratuito.');
            }

            try {
                // Tenta trocar o plano e cobrar a diferença (Prorata) imediatamente
                // usando o cartão já salvo.
                $usuario->subscription('default')->swapAndInvoice($id_preco);

                // Se passar, volta com sucesso
                return redirect()->route('subscription.index')
                    ->with('success', 'Plano atualizado com sucesso! O acesso foi liberado.');

            } catch (IncompletePayment $excecao) {
                // CENÁRIO DE ERRO: O cartão foi recusado ou pede autenticação 3DS.
                // Redireciona para a página de pagamento seguro do Cashier.
                return Inertia::location(route(
                    'cashier.payment',
                    [$excecao->payment->id, 'redirect' => route('subscription.index')]
                ));

            } catch (\Exception $e) {
                // Erro genérico? Manda para o portal para ele ver o que houve.
                session()->flash('error', 'Houve um problema no pagamento automático. Por favor, verifique seu cartão.');
                return Inertia::location(route('subscription.portal'));
            }
        }

        // ----------------------------------------------------------------------
        // CENÁRIO 2: Novo Assinante (Checkout Padrão)
        // ----------------------------------------------------------------------
        $checkout = $usuario->newSubscription('default', $id_preco)
            ->checkout([
                'success_url' => route('dashboard') . '?checkout=success',
                'cancel_url' => route('subscription.index') . '?checkout=cancel',
            ]);

        return Inertia::location($checkout->url);
    }

    /**
     * Portal do Cliente (para cancelar/atualizar cartão manualmente)
     */
    public function portal(Request $request)
    {
        $url = $request->user()->billingPortalUrl(route('subscription.index'));

        return Inertia::location($url);
    }
}
