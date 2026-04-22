<?php

namespace App\Console\Commands;

use App\Models\Plano;
use Illuminate\Console\Command;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

class SyncStripePlans extends Command
{
    protected $signature = 'stripe:sync-plans';

    protected $description = 'Sincroniza os planos pagos do banco com produtos e precos recorrentes do Stripe.';

    public function handle(): int
    {
        $chaveStripe = config('services.stripe.secret');

        if (!$chaveStripe) {
            $this->error('STRIPE_SECRET nao esta configurado.');
            return self::FAILURE;
        }

        Stripe::setApiKey($chaveStripe);

        Plano::query()
            ->where(function ($query) {
                $query->where('price_monthly_brl', '>', 0)
                    ->orWhere('price_yearly_brl', '>', 0);
            })
            ->orderBy('price_monthly_brl')
            ->each(function (Plano $plano) {
                $this->sincronizarPlano($plano);
            });

        $this->info('Sincronizacao concluida.');

        return self::SUCCESS;
    }

    private function sincronizarPlano(Plano $plano): void
    {
        $this->info("Processando plano {$plano->name}...");

        $produto = $this->obterOuCriarProduto($plano);

        if (!$plano->stripe_monthly_price_id && (int) $plano->price_monthly_brl > 0) {
            $plano->stripe_monthly_price_id = $this->obterOuCriarPreco(
                produtoId: $produto->id,
                slug: $plano->slug,
                intervalo: 'month',
                valorCentavos: (int) $plano->price_monthly_brl,
            );

            $this->line("  Mensal: {$plano->stripe_monthly_price_id}");
        }

        if (!$plano->stripe_yearly_price_id && (int) $plano->price_yearly_brl > 0) {
            $plano->stripe_yearly_price_id = $this->obterOuCriarPreco(
                produtoId: $produto->id,
                slug: $plano->slug,
                intervalo: 'year',
                valorCentavos: (int) $plano->price_yearly_brl,
            );

            $this->line("  Anual: {$plano->stripe_yearly_price_id}");
        }

        $plano->save();
    }

    private function obterOuCriarProduto(Plano $plano): Product
    {
        $resultado = Product::search([
            'query' => "metadata['slug']:'{$plano->slug}' AND active:'true'",
            'limit' => 1,
        ]);

        if (!empty($resultado->data)) {
            $produto = $resultado->data[0];

            if ($produto->name !== $plano->name) {
                return Product::update($produto->id, [
                    'name' => $plano->name,
                    'description' => "Assinatura {$plano->name}",
                ]);
            }

            return $produto;
        }

        return Product::create([
            'name' => $plano->name,
            'description' => "Assinatura {$plano->name}",
            'metadata' => [
                'slug' => $plano->slug,
            ],
        ]);
    }

    private function obterOuCriarPreco(string $produtoId, string $slug, string $intervalo, int $valorCentavos): string
    {
        $lookupKey = "{$slug}_{$intervalo}";

        $precos = Price::all([
            'lookup_keys' => [$lookupKey],
            'active' => true,
            'limit' => 1,
        ]);

        if (!empty($precos->data)) {
            $preco = $precos->data[0];

            if ((int) $preco->unit_amount === $valorCentavos) {
                return $preco->id;
            }

            Price::update($preco->id, [
                'active' => false,
            ]);
        }

        $novoPreco = Price::create([
            'product' => $produtoId,
            'unit_amount' => $valorCentavos,
            'currency' => 'brl',
            'recurring' => [
                'interval' => $intervalo,
            ],
            'lookup_key' => $lookupKey,
        ]);

        return $novoPreco->id;
    }
}
