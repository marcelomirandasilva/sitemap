<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class UpdatePlansSeeder extends Seeder
{
    public function run(): void
    {
        $idsPrecos = [
            // Preencha aqui apenas quando for necessario fixar IDs ja existentes no Stripe.
            // 'solo' => [
            //     'stripe_monthly_price_id' => 'price_xxx',
            //     'stripe_yearly_price_id' => 'price_yyy',
            // ],
        ];

        foreach ($idsPrecos as $slug => $dadosStripe) {
            $plano = Plano::where('slug', $slug)->first();

            if (!$plano) {
                echo "Plano '{$slug}' nao encontrado.\n";
                continue;
            }

            $plano->fill(array_filter($dadosStripe))->save();
            echo "Plano '{$plano->name}' atualizado com os precos Stripe.\n";
        }
    }
}
