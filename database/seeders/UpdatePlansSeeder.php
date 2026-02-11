<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class UpdatePlansSeeder extends Seeder
{
    public function run(): void
    {
        // Atualiza o plano Pro 1k (ID 2) com o ID fornecido pelo usuário
        $plan = Plan::find(2);
        if ($plan) {
            $plan->stripe_id = 'price_1SwWEOFOqAJ7yJjnWumEVcQE';
            $plan->save();
            echo "Plano '{$plan->name}' atualizado com sucesso!\n";
        } else {
            echo "Plano ID 2 não encontrado.\n";
        }
    }
}
