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

        // Se já tem assinatura, redireciona para portal ou avisa
        if ($user->subscribed('default')) {
            return redirect()->route('subscription.index')->with('error', 'Você já possui uma assinatura ativa.');
        }

        // Inicia Checkout do Stripe
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
