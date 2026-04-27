<?php

namespace App\Services;

use App\Models\EventoWebhookStripe;
use App\Models\MovimentacaoAssinatura;
use App\Models\PagamentoStripe;
use App\Models\Plano;
use App\Models\User;
use Carbon\Carbon;

class RastreabilidadeStripeService
{
    public function registrarEventoWebhook(array $payload, ?User $usuario = null): EventoWebhookStripe
    {
        $objeto = $payload['data']['object'] ?? [];
        $tipoEvento = (string) ($payload['type'] ?? 'desconhecido');
        $idAssinatura = null;
        $idFatura = null;

        if (str_starts_with($tipoEvento, 'invoice.')) {
            $idFatura = $objeto['id'] ?? null;
            $idAssinatura = $objeto['subscription'] ?? null;
        } elseif (str_starts_with($tipoEvento, 'customer.subscription.')) {
            $idAssinatura = $objeto['id'] ?? null;
        }

        return EventoWebhookStripe::updateOrCreate(
            [
                'stripe_event_id' => $payload['id'] ?? null,
            ],
            [
                'user_id' => $usuario?->id,
                'tipo_evento' => $tipoEvento,
                'stripe_customer_id' => $objeto['customer'] ?? null,
                'stripe_subscription_id' => $idAssinatura,
                'stripe_invoice_id' => $idFatura,
                'stripe_payment_intent_id' => $objeto['payment_intent'] ?? null,
                'payload' => $payload,
            ]
        );
    }

    public function marcarEventoProcessado(EventoWebhookStripe $evento, string $status = 'processado', ?string $erro = null): void
    {
        $evento->forceFill([
            'status_processamento' => $status,
            'erro_processamento' => $erro,
            'processado_em' => now(),
        ])->save();
    }

    public function registrarPagamentoPorInvoice(array $invoice, ?User $usuario = null, ?EventoWebhookStripe $evento = null, string $origem = 'webhook'): PagamentoStripe
    {
        $idPreco = $invoice['lines']['data'][0]['price']['id'] ?? null;
        $plano = $this->buscarPlanoPorPriceId($idPreco);
        $charge = $invoice['charge'] ?? null;

        return PagamentoStripe::updateOrCreate(
            [
                'stripe_invoice_id' => $invoice['id'] ?? null,
            ],
            [
                'user_id' => $usuario?->id,
                'plano_id' => $plano?->id,
                'evento_webhook_stripe_id' => $evento?->id,
                'origem' => $origem,
                'stripe_payment_intent_id' => $invoice['payment_intent'] ?? null,
                'stripe_charge_id' => is_string($charge) ? $charge : ($charge['id'] ?? null),
                'stripe_customer_id' => $invoice['customer'] ?? null,
                'stripe_subscription_id' => $invoice['subscription'] ?? null,
                'stripe_price_id' => $idPreco,
                'moeda' => $invoice['currency'] ?? null,
                'valor_total_centavos' => (int) ($invoice['amount_due'] ?? 0),
                'valor_pago_centavos' => (int) ($invoice['amount_paid'] ?? 0),
                'status' => $invoice['status'] ?? null,
                'descricao' => $invoice['description'] ?? null,
                'motivo_cobranca' => $invoice['billing_reason'] ?? null,
                'invoice_pdf_url' => $invoice['invoice_pdf'] ?? null,
                'hosted_invoice_url' => $invoice['hosted_invoice_url'] ?? null,
                'pago_em' => !empty($invoice['status_transitions']['paid_at'])
                    ? Carbon::createFromTimestamp($invoice['status_transitions']['paid_at'])
                    : null,
                'dados' => $invoice,
            ]
        );
    }

    public function registrarMovimentacaoAssinatura(array $dados): MovimentacaoAssinatura
    {
        if (!empty($dados['stripe_event_id'])) {
            return MovimentacaoAssinatura::updateOrCreate(
                ['stripe_event_id' => $dados['stripe_event_id']],
                $dados
            );
        }

        return MovimentacaoAssinatura::create($dados);
    }

    protected function buscarPlanoPorPriceId(?string $idPreco): ?Plano
    {
        if (!$idPreco) {
            return null;
        }

        return Plano::query()
            ->where('stripe_monthly_price_id', $idPreco)
            ->orWhere('stripe_yearly_price_id', $idPreco)
            ->first();
    }
}
