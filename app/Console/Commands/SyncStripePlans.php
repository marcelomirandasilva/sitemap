<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Plan;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class SyncStripePlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:sync-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza os planos do banco de dados com o Stripe, criando produtos e preços faltantes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronização com o Stripe...');

        // Configura a chave da API
        Stripe::setApiKey(config('services.stripe.secret'));

        $plans = Plan::all();

        foreach ($plans as $plan) {
            if ($plan->stripe_id) {
                $this->info("Plano '{$plan->name}' já possui ID Stripe: {$plan->stripe_id}. Pulando.");
                continue;
            }

            $this->info("Criando plano '{$plan->name}' no Stripe...");

            try {
                // 1. Cria o Produto
                $product = Product::create([
                    'name' => $plan->name,
                    'description' => "Assinatura {$plan->name} - Limite de {$plan->max_pages} páginas",
                ]);

                // 2. Cria o Preço (Considerando Mensal em BRL por padrão para simplificar por enquanto)
                // Se o plano for grátis (preço 0), não criamos preço no Stripe (ou lógica específica)
                if ($plan->price_monthly_brl > 0) {
                    $price = Price::create([
                        'product' => $product->id,
                        'unit_amount' => $plan->price_monthly_brl, // O valor no banco já deve estar em centavos? Vamos assumir que sim.
                        'currency' => 'brl',
                        'recurring' => ['interval' => 'month'],
                    ]);

                    // Salva o ID do PREÇO na tabela, pois é o que o Checkout usa
                    $plan->stripe_id = $price->id;
                } else {
                    // Lógica para plano grátis (se necessário, talvez não precise de ID do Stripe para checkout)
                    $this->warn("Plano '{$plan->name}' é gratuito. Nenhum preço criado no Stripe.");
                    // Se precisar de um produto para plano free, seria diferente, mas geralmente free não passa no checkout do Stripe da mesma forma.
                }

                $plan->save();

                $this->info("Sucesso! ID Stripe gerado: {$plan->stripe_id}");

            } catch (\Exception $e) {
                $this->error("Erro ao criar plano '{$plan->name}': " . $e->getMessage());
            }
        }

        $this->info('Sincronização concluída!');
    }
}
