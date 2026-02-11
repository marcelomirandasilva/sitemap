<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // 1. FREE
            [
                'slug' => 'free',
                'name' => 'Free',
                'max_pages' => 100,
                'price_monthly_brl' => 0,
                'price_yearly_brl' => 0,
                'price_monthly_usd' => 0,
                'price_yearly_usd' => 0,
                'max_projects' => 5,
                'has_advanced_features' => false,
            ],
            // 2. Pro 1k (R$ 35,90/mês -> R$ 344,64/ano)
            [
                'slug' => 'pro-1k',
                'name' => 'Pro 1k',
                'max_pages' => 1000,
                'price_monthly_usd' => 599,
                'price_yearly_usd' => 5750,  // 20% OFF
                'price_monthly_brl' => 3590,
                'price_yearly_brl' => 34464, // 20% OFF
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
            // 3. Pro 5k (R$ 53,90/mês -> R$ 517,44/ano)
            [
                'slug' => 'pro-5k',
                'name' => 'Pro 5k',
                'max_pages' => 5000,
                'price_monthly_usd' => 899,
                'price_yearly_usd' => 8630,  // 20% OFF
                'price_monthly_brl' => 5390,
                'price_yearly_brl' => 51744, // 20% OFF
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
            // 4. Pro 10k (R$ 71,90/mês -> R$ 690,24/ano)
            [
                'slug' => 'pro-10k',
                'name' => 'Pro 10k',
                'max_pages' => 10000,
                'price_monthly_usd' => 1199,
                'price_yearly_usd' => 11510, // 20% OFF
                'price_monthly_brl' => 7190,
                'price_yearly_brl' => 69024, // 20% OFF
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
            // 5. Pro 25k (R$ 107,90/mês -> R$ 1.035,84/ano)
            [
                'slug' => 'pro-25k',
                'name' => 'Pro 25k',
                'max_pages' => 25000,
                'price_monthly_usd' => 1799,
                'price_yearly_usd' => 17270, // 20% OFF
                'price_monthly_brl' => 10790,
                'price_yearly_brl' => 103584, // 20% OFF
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
            // 6. Pro 50k (R$ 143,90/mês -> R$ 1.381,44/ano)
            [
                'slug' => 'pro-50k',
                'name' => 'Pro 50k',
                'max_pages' => 50000,
                'price_monthly_usd' => 2399,
                'price_yearly_usd' => 23030, // 20% OFF
                'price_monthly_brl' => 14390,
                'price_yearly_brl' => 138144, // 20% OFF
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
        ];

        $stripeService = new \App\Services\StripePlanService();

        foreach ($plans as $plan) {
            $stripeIds = $stripeService->syncPlan($plan);

            DB::table('plans')->upsert(
                array_merge($plan, [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'stripe_monthly_price_id' => $stripeIds['stripe_monthly_price_id'],
                    'stripe_yearly_price_id' => $stripeIds['stripe_yearly_price_id'],
                ]),
                ['slug'] // Garante que atualiza se o slug já existir
            );
        }
    }
}