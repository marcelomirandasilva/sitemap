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
        return Inertia::render('Subscription/Index', [
            // Trazendo planos ordenados pelo preço
            'plans' => Plan::orderBy('price_monthly_brl', 'asc')->get(),

            // Dados da assinatura atual
            'currentSubscription' => auth()->user()->subscription('default'),

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
                    ->with('error', 'Houve um problema no pagamento automátio. Por favor, verifique seu cartão.');
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