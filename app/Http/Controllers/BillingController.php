<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    /**
     * Lista o histórico de faturas do usuário.
     */
    public function index(Request $request)
    {
        $invoices = [];

        // Verifica se o usuário tem faturas no Stripe
        if ($request->user()->hasStripeId()) {
            try {
                // Recupera as faturas (invoices) do Stripe via Cashier
                // O método invoices() retorna uma coleção de objetos Laravel\Cashier\Invoice
                $stripeInvoices = $request->user()->invoices();

                $invoices = $stripeInvoices->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'date' => $invoice->date()->toIso8601String(),
                        'total' => $invoice->total(), // Já formatado pelo Cashier se usar o método correto, mas aqui retorna valor cru ou objeto. Vamos ajustar no frontend ou formatar aqui.
                        'total_formatted' => $invoice->realTotal(), // Cashier costuma ter helpers, mas vamos simplificar enviando dados brutos e formatando no frontend ou usar o método 'total' que retorna string formatada em algumas versões. Vamos garantir string.
                        // Na verdade, o Cashier moderno retorna um objeto Invoice do Stripe-PHP wrapado.
                        // O método ->total() do Cashier retorna uma string formatada (ex: "R$ 20,00"), o que é ótimo para exibição.
                        'status' => $invoice->status,
                        'invoice_pdf' => $invoice->invoice_pdf, // Link direto para o PDF hospedado no Stripe
                    ];
                });
            } catch (\Exception $e) {
                // Em caso de erro (api key inválida, etc), retorna lista vazia para não quebrar a tela
                \Log::error('Erro ao buscar faturas: ' . $e->getMessage());
            }
        }

        return Inertia::render('Billing/Index', [
            'invoices' => $invoices,
        ]);
    }
}
