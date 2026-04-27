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
        $pagamentosLocais = $user->pagamentosStripe()
            ->with('plano')
            ->latest('pago_em')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(function ($pagamento) {
                return [
                    'id' => $pagamento->id,
                    'stripe_invoice_id' => $pagamento->stripe_invoice_id,
                    'status' => $pagamento->status,
                    'origem' => $pagamento->origem,
                    'descricao' => $pagamento->descricao,
                    'motivo_cobranca' => $pagamento->motivo_cobranca,
                    'moeda' => strtoupper((string) $pagamento->moeda),
                    'valor_pago_centavos' => $pagamento->valor_pago_centavos,
                    'valor_total_centavos' => $pagamento->valor_total_centavos,
                    'pago_em' => optional($pagamento->pago_em)->toIso8601String(),
                    'invoice_pdf_url' => $pagamento->invoice_pdf_url,
                    'hosted_invoice_url' => $pagamento->hosted_invoice_url,
                    'plano' => $pagamento->plano?->name,
                ];
            })
            ->values();

        $movimentacoesLocais = $user->movimentacoesAssinatura()
            ->with(['planoOrigem', 'planoDestino'])
            ->latest()
            ->limit(30)
            ->get()
            ->map(function ($movimentacao) {
                return [
                    'id' => $movimentacao->id,
                    'origem' => $movimentacao->origem,
                    'tipo_movimentacao' => $movimentacao->tipo_movimentacao,
                    'status' => $movimentacao->status,
                    'descricao' => $movimentacao->descricao,
                    'stripe_subscription_id' => $movimentacao->stripe_subscription_id,
                    'stripe_price_id' => $movimentacao->stripe_price_id,
                    'plano_origem' => $movimentacao->planoOrigem?->name,
                    'plano_destino' => $movimentacao->planoDestino?->name,
                    'created_at' => optional($movimentacao->created_at)->toIso8601String(),
                ];
            })
            ->values();

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
            'pagamentos_locais' => $pagamentosLocais,
            'movimentacoes_locais' => $movimentacoesLocais,
        ]);
    }
}
