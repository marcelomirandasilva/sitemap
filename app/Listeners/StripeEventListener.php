<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Log;

class StripeEventListener
{
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

    protected function handleSubscriptionUpdate($subscription)
    {
        $stripeId = $subscription['customer'];
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $status = $subscription['status'];

        // Só atualiza se o status for 'active' ou 'trialing'
        if (!in_array($status, ['active', 'trialing'])) {
            return;
        }

        Log::info("Webhook Stripe: Atualizando plano para customer {$stripeId}, preço {$priceId}");

        if (!$priceId) {
            return;
        }

        // 1. Achar o usuário pelo ID do Stripe
        $user = User::where('stripe_id', $stripeId)->first();

        // Se não achou pelo ID, tenta pelo e-mail (caso o webhook chegue antes do sync ou seja a primeira assinatura)
        if (!$user) {
            try {
                // Configura a chave se necessário (geralmente o Cashier já faz, mas por garantia)
                if (!\Stripe\Stripe::getApiKey()) {
                    \Stripe\Stripe::setApiKey(config('cashier.secret'));
                }

                $customer = \Stripe\Customer::retrieve($stripeId);

                if ($customer && $customer->email) {
                    $user = User::where('email', $customer->email)->first();

                    if ($user) {
                        $user->stripe_id = $stripeId;
                        $user->save();
                        Log::info("Webhook Stripe: Usuário {$user->id} ({$user->email}) vinculado ao Customer {$stripeId}.");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Webhook Stripe: Erro ao buscar customer {$stripeId}: " . $e->getMessage());
            }
        }

        if ($user) {
            // 2. Achar o plano local pelo ID do preço do Stripe (Mensal ou Anual)
            $plan = Plan::where('stripe_monthly_price_id', $priceId)
                ->orWhere('stripe_yearly_price_id', $priceId)
                ->first();

            if ($plan) {
                $user->plan_id = $plan->id;
                $user->save();
                Log::info("Webhook Stripe: Plano do usuário {$user->id} atualizado para {$plan->name}");
            } else {
                Log::warning("Webhook Stripe: Plano local não encontrado para o price_id {$priceId}");
            }
        }
    }

    protected function handleSubscriptionCancelled($subscription)
    {
        $stripeId = $subscription['customer'];
        $user = User::where('stripe_id', $stripeId)->first();

        if ($user) {
            // Remove o plano (volta para free/null)
            $user->plan_id = null; // Ou ID do plano free se existir
            $user->save();
            Log::info("Webhook Stripe: Assinatura cancelada para usuário {$user->id}. Plano removido.");
        }
    }

    protected function handlePaymentFailed($invoice)
    {
        $stripeId = $invoice['customer'];
        Log::warning("Webhook Stripe: Pagamento falhou para customer {$stripeId}. Verifique o status da assinatura.");
        // O Cashier já lidar com o update da tabela subscriptions para 'past_due', 
        // mas aqui podemos enviar notificação extra se quisermos.
    }
}
