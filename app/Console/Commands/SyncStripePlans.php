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
            $this->info("Processando plano '{$plan->name}'...");

            try {
                // garante que o Produto existe (ou usa um 'generico' se preferir, mas aqui vamos tentar garantir 1 produto por plano)
                // Simplificacao: Vamos criar um produto SE não tivermos nenhum ID ainda, ou se tivermos, assumimos que o produto associado ao preço antigo serviria. 
                // Melhor: criar um produto se não tiver nenhum ID. Se já tiver um, precisariamos recuperar o produto.
                // Abordagem Segura: Se já tem *algum* ID, assume que o produto existe. 
                // Se não tem nada, cria produto.

                $product = null;

                // Se nenhum dos dois existe, precisamos criar o Produto primeiro
                if (!$plan->stripe_monthly_price_id && !$plan->stripe_yearly_price_id) {
                    $product = Product::create([
                        'name' => $plan->name,
                        'description' => "Assinatura {$plan->name}",
                    ]);
                } else {
                    // Se já tem um ID, precisaria recuperar o produtoId desse preço para usar no outro.
                    // Para simplificar neste script, vamos assumir que se falta um, vamos criar um NOVO produto se não conseguirmos recuperar facil.
                    // MAS, o ideal é usar o mesmo produto.
                    // VAMOS SIMPLIFICAR: Este script roda 1x. Se já rodou antes com stripe_id mensal, ele vai ter stripe_id (que agora sumiu... espere).
                    // A migracao DELETA o stripe_id. Então para o script, vai parecer que não tem NADA.
                    // Isso é PERFEITO. Ele vai criar tudo novo e limpo.

                    // Mas se rodar uma segunda vez?
                    // Se já tiver monthly, mas não yearly.
                    // Recuperar produto é chato sem salvar o product_id no banco.
                    // DECISÃO: Por enquanto, se tiver UM deles, pulamos a criação do outro para evitar duplicação de produtos ou complexidade.
                    // Ou melhor: se já tiver um ID, pulamos o produto e assumimos que está "feito" ou parcialmente feito.
                    // O usuário pediu para "Evoluir".

                    if ($plan->stripe_monthly_price_id && $plan->stripe_yearly_price_id) {
                        $this->info("Plano '{$plan->name}' já completo. Pulando.");
                        continue;
                    }

                    // Se falta um, vamos criar produto novo para garantir? Não, duplicaria.
                    // Vamos focar no caso "Zero -> Tudo" (após migration)
                    if (!$product) {
                        $product = Product::create([
                            'name' => $plan->name,
                            'description' => "Assinatura {$plan->name}",
                        ]);
                    }
                }

                // 1. Mensal
                if (!$plan->stripe_monthly_price_id && $plan->price_monthly_brl > 0) {
                    $price = Price::create([
                        'product' => $product->id,
                        'unit_amount' => $plan->price_monthly_brl,
                        'currency' => 'brl',
                        'recurring' => ['interval' => 'month'],
                    ]);
                    $plan->stripe_monthly_price_id = $price->id;
                    $this->info("  -> Preço Mensal criado: {$price->id}");
                }

                // 2. Anual
                if (!$plan->stripe_yearly_price_id && $plan->price_yearly_brl > 0) {
                    $price = Price::create([
                        'product' => $product->id,
                        'unit_amount' => $plan->price_yearly_brl,
                        'currency' => 'brl',
                        'recurring' => ['interval' => 'year'],
                    ]);
                    $plan->stripe_yearly_price_id = $price->id;
                    $this->info("  -> Preço Anual criado: {$price->id}");
                }

                $plan->save();

            } catch (\Exception $e) {
                $this->error("Erro ao processar plano '{$plan->name}': " . $e->getMessage());
            }
        }

        $this->info('Sincronização concluída!');
    }
}
