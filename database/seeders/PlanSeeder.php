<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar tabela antes de rodar (opcional, mas bom para garantir limpeza se não for migrate:fresh)
        // Plan::truncate(); 

        $plans = [
            // 1. Free
            [
                'slug' => 'free',
                'name' => 'Free',
                'stripe_monthly_price_id' => null,
                'stripe_yearly_price_id' => null,
                'price_monthly_usd' => 0,
                'price_yearly_usd' => 0,
                'price_monthly_brl' => 0,
                'price_yearly_brl' => 0,
                'max_pages' => 500,
                'max_projects' => 1,
                'has_advanced_features' => false,
                'update_frequency' => 'Manual',
                'ideal_for' => 'Testes e sites pequenos',
            ],
            // 2. Solo
            [
                'slug' => 'solo',
                'name' => 'Solo',
                // $5.99 -> R$ 35.90 | $50.28/yr -> R$ 301.68
                'price_monthly_usd' => 599,
                'price_yearly_usd' => 5028,
                'price_monthly_brl' => 3590,
                'price_yearly_brl' => 30168,
                'max_pages' => 1600,
                'max_projects' => 3,
                'has_advanced_features' => false,
                'update_frequency' => 'Semanal',
                'ideal_for' => 'Sites pequenos com atualização',
            ],
            // 3. Growth
            [
                'slug' => 'growth',
                'name' => 'Growth',
                // $11.99 -> R$ 71.90 | $100.68/yr -> R$ 604.08
                'price_monthly_usd' => 1199,
                'price_yearly_usd' => 10068,
                'price_monthly_brl' => 7190,
                'price_yearly_brl' => 60408,
                'max_pages' => 16000,
                'max_projects' => 10,
                'has_advanced_features' => true,
                'update_frequency' => 'Diária',
                'ideal_for' => 'Conteúdo frequente / e-commerce',
            ],
            // 4. Pro
            [
                'slug' => 'pro',
                'name' => 'Pro',
                // $23.99 -> R$ 143.90 | $201.48/yr -> R$ 1208.88
                'price_monthly_usd' => 2399,
                'price_yearly_usd' => 20148,
                'price_monthly_brl' => 14390,
                'price_yearly_brl' => 120888,
                'max_pages' => 80000,
                'max_projects' => 25,
                'has_advanced_features' => true,
                'update_frequency' => 'Diária',
                'ideal_for' => 'SEO avançado e relatórios',
            ],
            // 5. Scale
            [
                'slug' => 'scale',
                'name' => 'Scale',
                // $47.99 -> R$ 287.90 | $403.08/yr -> R$ 2418.48
                'price_monthly_usd' => 4799,
                'price_yearly_usd' => 40308,
                'price_monthly_brl' => 28790,
                'price_yearly_brl' => 241848,
                'max_pages' => 320000,
                'max_projects' => 50,
                'has_advanced_features' => true,
                'update_frequency' => 'Diária + delta crawl',
                'ideal_for' => 'Agências e sites grandes',
            ],
            // 6. Enterprise
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                // $149.99 -> R$ 899.90 | $1259.88/yr -> R$ 7559.28
                'price_monthly_usd' => 14999,
                'price_yearly_usd' => 125988,
                'price_monthly_brl' => 89990,
                'price_yearly_brl' => 755928,
                'max_pages' => 1600000,
                'max_projects' => 100,
                'has_advanced_features' => true,
                'update_frequency' => 'Custom',
                'ideal_for' => 'Portais gigantes / SLA',
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}