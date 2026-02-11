<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripePlanService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    public function syncPlan(array $planData): array
    {
        try {
            // 1. Sincronizar Produto
            $productId = $this->syncProduct($planData);

            // 2. Sincronizar Preços (Mensal e Anual)
            $monthlyPriceId = $planData['price_monthly_brl'] > 0
                ? $this->syncPrice($productId, $planData['price_monthly_brl'], 'month', $planData['slug'])
                : null;

            $yearlyPriceId = $planData['price_yearly_brl'] > 0
                ? $this->syncPrice($productId, $planData['price_yearly_brl'], 'year', $planData['slug'])
                : null;

            return [
                'stripe_product_id' => $productId,
                'stripe_monthly_price_id' => $monthlyPriceId,
                'stripe_yearly_price_id' => $yearlyPriceId,
            ];

        } catch (ApiErrorException $e) {
            // Em produção, logar o erro. Para seed, pode ser útil exibir.
            echo "Erro Stripe: " . $e->getMessage() . "\n";
            return [
                'stripe_product_id' => null,
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
            ];
        }
    }

    protected function syncProduct(array $plan): string
    {
        // Tenta buscar pelo slug nos metadados
        $products = $this->stripe->products->search([
            'query' => "metadata['slug']:'{$plan['slug']}' AND active:'true'",
            'limit' => 1,
        ]);

        if (count($products->data) > 0) {
            $product = $products->data[0];
            // Atualiza nome se mudou
            if ($product->name !== $plan['name']) {
                $this->stripe->products->update($product->id, ['name' => $plan['name']]);
            }
            return $product->id;
        }

        // Cria novo produto
        $newProduct = $this->stripe->products->create([
            'name' => $plan['name'],
            'metadata' => ['slug' => $plan['slug']],
        ]);

        return $newProduct->id;
    }

    protected function syncPrice(string $productId, int $amountBrl, string $interval, string $slug): string
    {
        $lookupKey = "{$slug}_{$interval}";

        // Tenta buscar preços com essa lookup_key
        $prices = $this->stripe->prices->all([
            'lookup_keys' => [$lookupKey],
            'limit' => 1,
        ]);

        if (count($prices->data) > 0) {
            $price = $prices->data[0];
            // Verifica se o valor bate. Se não bater, "arquiva" o antigo (opcional) ou cria um novo com nova lookup_key?
            // Simplificação: Se o valor for diferente, cria um novo e transfere a lookup_key.
            if ($price->unit_amount === $amountBrl) {
                return $price->id;
            }

            // Se o valor mudou, transfere a lookup_key para o novo preço
            $this->stripe->prices->update($price->id, ['lookup_key' => null]);
        }

        // Cria novo preço
        $newPrice = $this->stripe->prices->create([
            'unit_amount' => $amountBrl,
            'currency' => 'brl',
            'recurring' => ['interval' => $interval],
            'product' => $productId,
            'lookup_key' => $lookupKey,
            'transfer_lookup_key' => true, // Garante que rouba a key se existir em outro preço inativo/diferente
        ]);

        return $newPrice->id;
    }
}
