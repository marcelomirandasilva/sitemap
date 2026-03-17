<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Plan;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    /**
     * Exibe a tela de planos
     */
    public function index()
    {
        $subscription = auth()->user()->subscription('default');

        return Inertia::render('Subscription/Index', [
            // Trazendo planos ordenados pelo preço
            'plans' => Plan::orderBy('price_monthly_brl', 'asc')->get(),

            // Dados da assinatura atual
            'currentSubscription' => $subscription,
            'currentPriceId' => $subscription ? $subscription->stripe_price : null,
            'isCancelled' => $subscription ? $subscription->canceled() : false,
            'onGracePeriod' => $subscription ? $subscription->onGracePeriod() : false,
            'endsAt' => $subscription && $subscription->ends_at ? $subscription->ends_at->format('d/m/Y') : null,

            // Dados parciais do cartão para a mensagem de confirmação
            'userCardLast4' => auth()->user()->pm_last_four,
            'userCardBrand' => auth()->user()->pm_type,
        ]);
    }

    /**
     * Processa o Checkout ou a Troca de Plano (Swap)
     */
    public function checkout(Request $request, string $priceId)
    {
        $user = $request->user();

        // ----------------------------------------------------------------------
        // CENÁRIO 1: Usuário já é assinante (UPGRADE/DOWNGRADE)
        // ----------------------------------------------------------------------
        if ($user->subscribed('default')) {
            // Se o plano escolhido for o "Free" (identificado por não ter price_id no Stripe)
            $plan = Plan::where('stripe_monthly_price_id', $priceId)
                ->orWhere('stripe_yearly_price_id', $priceId)
                ->first();

            if (!$plan || (!$plan->stripe_monthly_price_id && !$plan->stripe_yearly_price_id)) {
                // Usuário quer voltar ao FREE -> Cancela a assinatura atual
                $user->subscription('default')->cancel();
                return redirect()->route('subscription.index')
                    ->with('success', 'Sua assinatura paga foi cancelada. Você continuará com os recursos atuais até o fim do período faturado e depois voltará ao plano gratuito.');
            }

            try {
                // Tenta trocar o plano e cobrar a diferença (Prorata) imediatamente
                // usando o cartão já salvo.
                $user->subscription('default')->swapAndInvoice($priceId);

                // Se passar, volta com sucesso
                return redirect()->route('subscription.index')
                    ->with('success', 'Plano atualizado com sucesso! O acesso foi liberado.');

            } catch (IncompletePayment $exception) {
                // CENÁRIO DE ERRO: O cartão foi recusado ou pede autenticação 3DS.
                // Redireciona para a página de pagamento seguro do Cashier.
                return redirect()->route(
                    'cashier.payment',
                    [$exception->payment->id, 'redirect' => route('subscription.index')]
                );

            } catch (\Exception $e) {
                // Erro genérico? Manda para o portal para ele ver o que houve.
                return redirect()->route('subscription.portal')
                    ->with('error', 'Houve um problema no pagamento automático. Por favor, verifique seu cartão.');
            }
        }

        // ----------------------------------------------------------------------
        // CENÁRIO 2: Novo Assinante (Checkout Padrão)
        // ----------------------------------------------------------------------
        return $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('dashboard') . '?checkout=success',
                'cancel_url' => route('subscription.index') . '?checkout=cancel',
            ]);
    }

    /**
     * Portal do Cliente (para cancelar/atualizar cartão manualmente)
     */
    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('subscription.index'));
    }
}