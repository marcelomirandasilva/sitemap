<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class FaturamentoController extends Controller
{
    /**
     * Lista o histórico de faturas do usuário e status da assinatura.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $planoEfetivo = $user->planoEfetivo();
        $faturas = [];

        // Verifica se o usuário tem faturas no Stripe
        if ($user->hasStripeId()) {
            try {
                $stripeInvoices = $user->invoices();
                $faturas = $stripeInvoices->map(function ($fatura) {
                    return [
                        'id' => $fatura->id,
                        'date' => $fatura->date()->toIso8601String(),
                        'total' => $fatura->total(),
                        'total_formatted' => $fatura->realTotal(),
                        'status' => $fatura->status,
                        'invoice_pdf' => $fatura->invoice_pdf,
                    ];
                });
            } catch (\Exception $e) {
                \Log::error('Erro ao buscar faturas: ' . $e->getMessage());
            }
        }

        // 1. Buscar a assinatura ativa (default) com o Cashier
        $assinatura = $user->assinaturaPadrao();

        // 2. Extrair dados cruciais do plano para a UI
        $assinatura_ativa = null;
        if ($planoEfetivo && $user->planoExigeAssinatura($planoEfetivo) && $user->possuiAcessoPagoVigente()) {
            $assinatura_ativa = [
                'name' => $planoEfetivo->name,
                'status' => $assinatura ? $assinatura->stripe_status : 'trialing',
                'cancel_at_period_end' => $assinatura ? $assinatura->onGracePeriod() : false,
                'ends_at' => $assinatura && $assinatura->ends_at ? $assinatura->ends_at->toIso8601String() : null,
            ];
        }

        return Inertia::render('Faturamento/Index', [
            'faturas' => $faturas,
            'assinatura_ativa' => $assinatura_ativa,
        ]);
    }
}
