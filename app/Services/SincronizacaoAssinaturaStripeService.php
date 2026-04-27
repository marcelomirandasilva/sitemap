<?php

namespace App\Services;

use App\Models\Plano;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SincronizacaoAssinaturaStripeService
{
    public function sincronizarPorSessaoCheckout(User $usuario, string $idSessaoCheckout): bool
    {
        $this->configurarChaveStripe();

        $sessaoCheckout = \Stripe\Checkout\Session::retrieve($idSessaoCheckout, [
            'expand' => ['subscription.items.data.price', 'customer'],
        ]);

        if (($sessaoCheckout->mode ?? null) !== 'subscription') {
            Log::warning('Checkout Stripe ignorado: sessao nao e de subscription.', [
                'session_id' => $idSessaoCheckout,
                'user_id' => $usuario->id,
            ]);

            return false;
        }

        if (($sessaoCheckout->status ?? null) !== 'complete') {
            Log::warning('Checkout Stripe ignorado: sessao ainda nao concluida.', [
                'session_id' => $idSessaoCheckout,
                'user_id' => $usuario->id,
                'status' => $sessaoCheckout->status ?? null,
            ]);

            return false;
        }

        $this->validarSessaoPertenceAoUsuario($usuario, $sessaoCheckout);

        $assinaturaStripe = $sessaoCheckout->subscription;

        if (is_string($assinaturaStripe)) {
            $assinaturaStripe = \Stripe\Subscription::retrieve($assinaturaStripe, [
                'expand' => ['items.data.price'],
            ]);
        }

        return $this->sincronizarAssinaturaStripe($usuario, $assinaturaStripe, $sessaoCheckout->customer);
    }

    public function sincronizarAssinaturaAtivaMaisRecente(User $usuario): bool
    {
        $this->configurarChaveStripe();

        $idClienteStripe = $usuario->stripe_id ?: $this->buscarClienteStripePorEmail($usuario->email)?->id;

        if (!$idClienteStripe) {
            Log::warning('Nao foi possivel localizar customer Stripe para reconciliar assinatura.', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
            ]);

            return false;
        }

        $assinaturas = \Stripe\Subscription::all([
            'customer' => $idClienteStripe,
            'status' => 'all',
            'limit' => 10,
            'expand' => ['data.items.data.price'],
        ]);

        $assinaturaStripe = collect($assinaturas->data ?? [])
            ->filter(fn ($assinatura) => in_array($assinatura->status ?? null, ['active', 'trialing'], true))
            ->sortByDesc(fn ($assinatura) => $assinatura->created ?? 0)
            ->first();

        if (!$assinaturaStripe) {
            Log::warning('Nenhuma assinatura ativa foi encontrada no Stripe para reconciliar.', [
                'user_id' => $usuario->id,
                'customer_id' => $idClienteStripe,
            ]);

            return false;
        }

        return $this->sincronizarAssinaturaStripe($usuario, $assinaturaStripe, $idClienteStripe);
    }

    protected function sincronizarAssinaturaStripe(User $usuario, object $assinaturaStripe, mixed $clienteStripe): bool
    {
        $itens = collect($assinaturaStripe->items->data ?? []);
        $primeiroItem = $itens->first();
        $idPreco = $primeiroItem->price->id ?? null;

        if (!$idPreco) {
            Log::warning('Assinatura Stripe sem stripe_price na reconciliacao.', [
                'user_id' => $usuario->id,
                'subscription_id' => $assinaturaStripe->id ?? null,
            ]);

            return false;
        }

        $plano = Plano::query()
            ->where('stripe_monthly_price_id', $idPreco)
            ->orWhere('stripe_yearly_price_id', $idPreco)
            ->first();

        if (!$plano) {
            Log::warning('Nao foi encontrado plano local para stripe_price na reconciliacao.', [
                'user_id' => $usuario->id,
                'stripe_price' => $idPreco,
            ]);

            return false;
        }

        $idClienteStripe = is_string($clienteStripe)
            ? $clienteStripe
            : ($clienteStripe->id ?? null);

        $usuario->forceFill([
            'stripe_id' => $idClienteStripe ?: $usuario->stripe_id,
            'plan_id' => $plano->id,
        ])->save();

        $assinaturaLocal = $usuario->subscriptions()
            ->where('stripe_id', $assinaturaStripe->id)
            ->first()
            ?? $usuario->subscriptions()->where('type', 'default')->first()
            ?? $usuario->subscriptions()->make([
                'type' => 'default',
            ]);

        $assinaturaLocal->fill([
            'type' => 'default',
            'stripe_id' => $assinaturaStripe->id,
            'stripe_status' => $assinaturaStripe->status,
            'stripe_price' => $itens->count() === 1 ? $idPreco : null,
            'quantity' => $itens->count() === 1 ? ($primeiroItem->quantity ?? null) : null,
            'trial_ends_at' => $this->converterTimestampStripe($assinaturaStripe->trial_end ?? null),
            'ends_at' => $this->resolverEndsAt($assinaturaStripe),
        ])->save();

        $idsItensMantidos = [];

        foreach ($itens as $item) {
            $itemLocal = $assinaturaLocal->items()->updateOrCreate(
                ['stripe_id' => $item->id],
                [
                    'stripe_product' => $item->price->product ?? '',
                    'stripe_price' => $item->price->id ?? '',
                    'quantity' => $item->quantity ?? null,
                ]
            );

            $idsItensMantidos[] = $itemLocal->id;
        }

        if (!empty($idsItensMantidos)) {
            $assinaturaLocal->items()
                ->whereNotIn('id', $idsItensMantidos)
                ->delete();
        }

        $usuario->unsetRelation('subscriptions');
        $usuario->setRelation('plano', $plano);

        Log::info('Assinatura Stripe reconciliada com sucesso apos checkout.', [
            'user_id' => $usuario->id,
            'plan_id' => $plano->id,
            'subscription_id' => $assinaturaStripe->id,
            'stripe_price' => $idPreco,
        ]);

        return true;
    }

    protected function validarSessaoPertenceAoUsuario(User $usuario, object $sessaoCheckout): void
    {
        $emailSessao = $sessaoCheckout->customer_details->email
            ?? $sessaoCheckout->customer_email
            ?? null;

        $idReferencia = (string) ($sessaoCheckout->client_reference_id ?? '');

        $emailConfere = $emailSessao && mb_strtolower($emailSessao) === mb_strtolower($usuario->email);
        $referenciaConfere = $idReferencia !== '' && $idReferencia === (string) $usuario->id;

        if ($emailConfere || $referenciaConfere) {
            return;
        }

        throw new \RuntimeException('A sessao de checkout nao pertence ao usuario autenticado.');
    }

    protected function buscarClienteStripePorEmail(string $email): ?object
    {
        $clientes = \Stripe\Customer::all([
            'email' => $email,
            'limit' => 1,
        ]);

        return $clientes->data[0] ?? null;
    }

    protected function configurarChaveStripe(): void
    {
        $chave = config('cashier.secret') ?: env('STRIPE_SECRET');

        if (!$chave) {
            throw new \RuntimeException('A chave secreta do Stripe nao esta configurada.');
        }

        if (\Stripe\Stripe::getApiKey() !== $chave) {
            \Stripe\Stripe::setApiKey($chave);
        }
    }

    protected function converterTimestampStripe(?int $timestamp): ?Carbon
    {
        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }

    protected function resolverEndsAt(object $assinaturaStripe): ?Carbon
    {
        if (!empty($assinaturaStripe->cancel_at)) {
            return Carbon::createFromTimestamp($assinaturaStripe->cancel_at);
        }

        if (!empty($assinaturaStripe->cancel_at_period_end) && !empty($assinaturaStripe->current_period_end)) {
            return Carbon::createFromTimestamp($assinaturaStripe->current_period_end);
        }

        return null;
    }
}
