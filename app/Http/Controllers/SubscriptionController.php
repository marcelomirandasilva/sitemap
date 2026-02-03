<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Plan;

class SubscriptionController extends Controller
{
    /**
     * Exibe a tela de planos
     */
    public function index()
    {
        return Inertia::render('Subscription/Index', [
            'plans' => Plan::all(), // Supondo que usamos a tabela plans para exibir info
            'currentSubscription' => auth()->user()->subscription('default'),
        ]);
    }

    /**
     * Inicia o Checkout da Assinatura
     */
    public function checkout(Request $request, string $priceId)
    {
        $user = $request->user();

        // Se já tem assinatura, faz o SWAP (Troca de Plano)
        if ($user->subscribed('default')) {
            try {
                // Tenta trocar o plano usando o metodo de pagamento atual
                $user->subscription('default')->swapAndInvoice($priceId);

                // IMPORTANTE: Disparar evento de upgrade manualmente se necessário ou confiar no Webhook.
                // Como temos o StripeEventListener, o webhook vai atualizar o plan_id em breve.

                return redirect()->route('subscription.index')->with('success', 'Seu plano foi alterado com sucesso!');
            } catch (\Exception $e) {
                // Se falhar (ex: cartão recusado), redireciona para o portal ou mostra erro
                return redirect()->route('subscription.index')->with('error', 'Erro ao alterar o plano: ' . $e->getMessage());
            }
        }

        // Se NÃO tem assinatura, inicia Checkout do Stripe
        return $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('dashboard') . '?checkout=success',
                'cancel_url' => route('subscription.index') . '?checkout=cancel',
            ]);
    }

    /**
     * Portal do Cliente (para cancelar/atualizar cartão)
     */
    public function portal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('dashboard'));
    }
}
