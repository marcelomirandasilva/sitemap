<?php

namespace App\Listeners;

use App\Models\Plano;
use App\Models\User;
use App\Services\CentralNotificacoesService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    public function __construct(private CentralNotificacoesService $centralNotificacoes)
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

        switch ($type) {
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdate($data);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionCancelled($data);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($data);
                break;
        }
    }

    protected function handleSubscriptionUpdate($subscription): void
    {
        $stripeId = $subscription['customer'];
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $status = $subscription['status'];
        $usuario = $this->encontrarUsuarioPorClienteStripe($stripeId);

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

    protected function handleSubscriptionCancelled($subscription): void
    {
        $stripeId = $subscription['customer'];
        $usuario = $this->encontrarUsuarioPorClienteStripe($stripeId);

        if (!$usuario) {
            return;
        }

        $plano = $usuario->sincronizarPlanoComAssinatura();

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

    protected function handlePaymentFailed($invoice): void
    {
        $stripeId = $invoice['customer'];
        $usuario = $this->encontrarUsuarioPorClienteStripe($stripeId);

        if ($usuario) {
            $this->centralNotificacoes->notificarPlano(
                $usuario,
                'Falha no pagamento',
                'Nao foi possivel confirmar o pagamento da sua assinatura. Verifique o metodo de pagamento para evitar interrupcao do plano.',
                [
                    'Cliente Stripe: ' . $stripeId,
                ]
            );
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
