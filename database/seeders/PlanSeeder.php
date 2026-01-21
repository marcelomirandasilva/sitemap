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
                'max_pages' => 500,
                'price_monthly_brl' => 0,
                'price_yearly_brl' => 0,
                'price_monthly_usd' => 0,
                'price_yearly_usd' => 0,
                'max_projects' => 10, // Default conforme migration, mas explícito aqui
                'has_advanced_features' => false,
            ],
            // 2. Pro 1k
            [
                'slug' => 'pro-1k',
                'name' => 'Pro 1k',
                'max_pages' => 1000,
                'price_monthly_usd' => 599,
                'price_yearly_usd' => 5028,
                'price_monthly_brl' => 3590,
                'price_yearly_brl' => 30168,
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
            // 3. Pro 5k
            [
                'slug' => 'pro-5k',
                'name' => 'Pro 5k',
                'max_pages' => 5000,
                'price_monthly_usd' => 899,
                'price_yearly_usd' => 7548,
                'price_monthly_brl' => 5390,
                'price_yearly_brl' => 45288,
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
            // 4. Pro 10k
            [
                'slug' => 'pro-10k',
                'name' => 'Pro 10k',
                'max_pages' => 10000,
                'price_monthly_usd' => 1199,
                'price_yearly_usd' => 10068,
                'price_monthly_brl' => 7190,
                'price_yearly_brl' => 60408,
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
            // 5. Pro 25k
            [
                'slug' => 'pro-25k',
                'name' => 'Pro 25k',
                'max_pages' => 25000,
                'price_monthly_usd' => 1799,
                'price_yearly_usd' => 15108,
                'price_monthly_brl' => 10790,
                'price_yearly_brl' => 90648,
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
            // 6. Pro 50k
            [
                'slug' => 'pro-50k',
                'name' => 'Pro 50k',
                'max_pages' => 50000,
                'price_monthly_usd' => 2399,
                'price_yearly_usd' => 20148,
                'price_monthly_brl' => 14390,
                'price_yearly_brl' => 120888,
                'max_projects' => 10,
                'has_advanced_features' => true,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->upsert(
                array_merge($plan, [
                    'created_at' => now(),
                    'updated_at' => now()
                ]),
                ['slug']
            );
        }
    }
}