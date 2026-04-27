<?php

namespace App\Listeners;

use App\Models\Plano;
use App\Models\User;
use App\Services\CentralNotificacoesService;
use App\Services\RastreabilidadeStripeService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    public function __construct(
        private CentralNotificacoesService $centralNotificacoes,
        private RastreabilidadeStripeService $rastreabilidadeStripe
    )
    {
    }

    /**
     * Handle received Stripe webhooks.
     */
    public function handle(WebhookReceived $event)
    {
        $payload = $event->payload;
        $type = $payload['type'];
        $data = $payload['data']['object'];
        $usuario = isset($data['customer']) ? $this->encontrarUsuarioPorClienteStripe($data['customer']) : null;
        $registroEvento = $this->rastreabilidadeStripe->registrarEventoWebhook($payload, $usuario);

        try {
            switch ($type) {
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdate($data, $usuario, $registroEvento);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionCancelled($data, $usuario, $registroEvento);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handlePaymentSucceeded($data, $usuario, $registroEvento);
                    break;

                case 'invoice.payment_failed':
                    $this->handlePaymentFailed($data, $usuario, $registroEvento);
                    break;
            }

            $this->rastreabilidadeStripe->marcarEventoProcessado($registroEvento);
        } catch (\Throwable $erro) {
            $this->rastreabilidadeStripe->marcarEventoProcessado($registroEvento, 'erro', $erro->getMessage());
            throw $erro;
        }
    }

    protected function handleSubscriptionUpdate($subscription, ?User $usuario, $registroEvento): void
    {
        $stripeId = $subscription['customer'];
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $status = $subscription['status'];
        $planoAnteriorId = $usuario?->plan_id;

        if (!in_array($status, ['active', 'trialing'], true)) {
            if ($usuario) {
                $plano = $usuario->sincronizarPlanoComAssinatura();

                Log::info("Webhook Stripe: assinatura sem acesso vigente para usuario {$usuario->id}. Plano sincronizado para " . ($plano?->name ?? 'nenhum'));
            }

            return;
        }

        Log::info("Webhook Stripe: atualizando plano para customer {$stripeId}, preco {$priceId}");

        if (!$usuario || !$priceId) {
            return;
        }

        $plano = Plano::query()
            ->where('stripe_monthly_price_id', $priceId)
            ->orWhere('stripe_yearly_price_id', $priceId)
            ->first();

        if (!$plano) {
            Log::warning("Webhook Stripe: plano local nao encontrado para o price_id {$priceId}");
            return;
        }

        $usuario->plan_id = $plano->id;
        $usuario->save();
        $usuario->setRelation('plano', $plano);

        $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
            'user_id' => $usuario->id,
            'plano_origem_id' => $planoAnteriorId,
            'plano_destino_id' => $plano->id,
            'evento_webhook_stripe_id' => $registroEvento->id,
            'origem' => 'webhook',
            'tipo_movimentacao' => 'assinatura_atualizada',
            'status' => $status,
            'stripe_customer_id' => $stripeId,
            'stripe_subscription_id' => $subscription['id'] ?? null,
            'stripe_price_id' => $priceId,
            'stripe_event_id' => $registroEvento->stripe_event_id,
            'descricao' => "Plano reconciliado para {$plano->name} via webhook Stripe.",
            'dados' => $subscription,
        ]);

        $this->centralNotificacoes->notificarPlano(
            $usuario,
            'Plano atualizado',
            "Sua assinatura {$plano->name} esta ativa no GenMap.",
            [
                'Plano: ' . $plano->name,
                'Status Stripe: ' . $status,
            ]
        );

        Log::info("Webhook Stripe: plano do usuario {$usuario->id} atualizado para {$plano->name}");
    }

    protected function handleSubscriptionCancelled($subscription, ?User $usuario, $registroEvento): void
    {
        if (!$usuario) {
            return;
        }

        $planoAnteriorId = $usuario->plan_id;
        $plano = $usuario->sincronizarPlanoComAssinatura();

        $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
            'user_id' => $usuario->id,
            'plano_origem_id' => $planoAnteriorId,
            'plano_destino_id' => $plano?->id,
            'evento_webhook_stripe_id' => $registroEvento->id,
            'origem' => 'webhook',
            'tipo_movimentacao' => 'assinatura_cancelada',
            'status' => $subscription['status'] ?? null,
            'stripe_customer_id' => $subscription['customer'] ?? null,
            'stripe_subscription_id' => $subscription['id'] ?? null,
            'stripe_price_id' => $subscription['items']['data'][0]['price']['id'] ?? null,
            'stripe_event_id' => $registroEvento->stripe_event_id,
            'descricao' => 'Assinatura cancelada ou encerrada via Stripe.',
            'dados' => $subscription,
        ]);

        $this->centralNotificacoes->notificarPlano(
            $usuario,
            'Assinatura cancelada',
            'Sua assinatura paga foi cancelada ou encerrada. O acesso sera ajustado conforme o periodo vigente.',
            [
                'Plano atual: ' . ($plano?->name ?? 'Free'),
            ]
        );

        Log::info("Webhook Stripe: assinatura cancelada para usuario {$usuario->id}. Plano ajustado para " . ($plano?->name ?? 'nenhum') . '.');
    }

    protected function handlePaymentSucceeded($invoice, ?User $usuario, $registroEvento): void
    {
        $this->rastreabilidadeStripe->registrarPagamentoPorInvoice($invoice, $usuario, $registroEvento, 'webhook');

        if ($usuario) {
            $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
                'user_id' => $usuario->id,
                'plano_origem_id' => $usuario->plan_id,
                'plano_destino_id' => $usuario->plan_id,
                'evento_webhook_stripe_id' => $registroEvento->id,
                'origem' => 'webhook',
                'tipo_movimentacao' => 'pagamento_confirmado',
                'status' => $invoice['status'] ?? null,
                'stripe_customer_id' => $invoice['customer'] ?? null,
                'stripe_subscription_id' => $invoice['subscription'] ?? null,
                'stripe_price_id' => $invoice['lines']['data'][0]['price']['id'] ?? null,
                'stripe_event_id' => $registroEvento->stripe_event_id,
                'descricao' => 'Pagamento confirmado pela Stripe.',
                'dados' => $invoice,
            ]);
        }
    }

    protected function handlePaymentFailed($invoice, ?User $usuario, $registroEvento): void
    {
        $stripeId = $invoice['customer'];
        $this->rastreabilidadeStripe->registrarPagamentoPorInvoice($invoice, $usuario, $registroEvento, 'webhook');

        if ($usuario) {
            $this->centralNotificacoes->notificarPlano(
                $usuario,
                'Falha no pagamento',
                'Nao foi possivel confirmar o pagamento da sua assinatura. Verifique o metodo de pagamento para evitar interrupcao do plano.',
                [
                    'Cliente Stripe: ' . $stripeId,
                ]
            );

            $this->rastreabilidadeStripe->registrarMovimentacaoAssinatura([
                'user_id' => $usuario->id,
                'plano_origem_id' => $usuario->plan_id,
                'plano_destino_id' => $usuario->plan_id,
                'evento_webhook_stripe_id' => $registroEvento->id,
                'origem' => 'webhook',
                'tipo_movimentacao' => 'pagamento_falhou',
                'status' => $invoice['status'] ?? null,
                'stripe_customer_id' => $invoice['customer'] ?? null,
                'stripe_subscription_id' => $invoice['subscription'] ?? null,
                'stripe_price_id' => $invoice['lines']['data'][0]['price']['id'] ?? null,
                'stripe_event_id' => $registroEvento->stripe_event_id,
                'descricao' => 'Pagamento recusado ou nao concluido na Stripe.',
                'dados' => $invoice,
                ]);
        }

        Log::warning("Webhook Stripe: pagamento falhou para customer {$stripeId}. Verifique o status da assinatura.");
    }

    protected function encontrarUsuarioPorClienteStripe(string $stripeId): ?User
    {
        $usuario = User::where('stripe_id', $stripeId)->first();

        if ($usuario) {
            return $usuario;
        }

        try {
            if (!\Stripe\Stripe::getApiKey()) {
                \Stripe\Stripe::setApiKey(config('cashier.secret'));
            }

            $customer = \Stripe\Customer::retrieve($stripeId);

            if (!$customer || !$customer->email) {
                return null;
            }

            $usuario = User::where('email', $customer->email)->first();

            if ($usuario) {
                $usuario->stripe_id = $stripeId;
                $usuario->save();

                Log::info("Webhook Stripe: usuario {$usuario->id} ({$usuario->email}) vinculado ao customer {$stripeId}.");
            }

            return $usuario;
        } catch (\Throwable $erro) {
            Log::error("Webhook Stripe: erro ao buscar customer {$stripeId}: " . $erro->getMessage());

            return null;
        }
    }
}
