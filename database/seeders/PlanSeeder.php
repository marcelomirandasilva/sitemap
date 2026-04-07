<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planos = [
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
                'permite_imagens' => true,
                'permite_videos' => true,
                'permite_noticias' => false,
                'permite_mobile' => false,
                'permite_compactacao' => false,
                'permite_cache_crawler' => false,
                'permite_padroes_exclusao' => false,
                'permite_politicas_crawl' => false,
                'update_frequency' => 'manual',
                'ideal_for' => 'Testar o servico em um unico site',
            ],
            [
                'slug' => 'solo',
                'name' => 'Solo',
                'price_monthly_usd' => 599,
                'price_yearly_usd' => 5028,
                'price_monthly_brl' => 3590,
                'price_yearly_brl' => 30168,
                'max_pages' => 1600,
                'max_projects' => 3,
                'has_advanced_features' => false,
                'permite_imagens' => true,
                'permite_videos' => true,
                'permite_noticias' => false,
                'permite_mobile' => false,
                'permite_compactacao' => false,
                'permite_cache_crawler' => false,
                'permite_padroes_exclusao' => false,
                'permite_politicas_crawl' => false,
                'update_frequency' => 'semanal',
                'ideal_for' => 'Operar ate 3 sites com atualizacao semanal e midia',
            ],
            [
                'slug' => 'growth',
                'name' => 'Growth',
                'price_monthly_usd' => 1199,
                'price_yearly_usd' => 10068,
                'price_monthly_brl' => 7190,
                'price_yearly_brl' => 60408,
                'max_pages' => 16000,
                'max_projects' => 10,
                'has_advanced_features' => true,
                'permite_imagens' => true,
                'permite_videos' => true,
                'permite_noticias' => true,
                'permite_mobile' => true,
                'permite_compactacao' => true,
                'permite_cache_crawler' => true,
                'permite_padroes_exclusao' => true,
                'permite_politicas_crawl' => true,
                'update_frequency' => 'diario',
                'ideal_for' => 'Conteudo frequente e e-commerce em crescimento',
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price_monthly_usd' => 2399,
                'price_yearly_usd' => 20148,
                'price_monthly_brl' => 14390,
                'price_yearly_brl' => 120888,
                'max_pages' => 80000,
                'max_projects' => 25,
                'has_advanced_features' => true,
                'permite_imagens' => true,
                'permite_videos' => true,
                'permite_noticias' => true,
                'permite_mobile' => true,
                'permite_compactacao' => true,
                'permite_cache_crawler' => true,
                'permite_padroes_exclusao' => true,
                'permite_politicas_crawl' => true,
                'update_frequency' => 'diario',
                'ideal_for' => 'SEO avancado com mais controle tecnico',
            ],
            [
                'slug' => 'scale',
                'name' => 'Scale',
                'price_monthly_usd' => 4799,
                'price_yearly_usd' => 40308,
                'price_monthly_brl' => 28790,
                'price_yearly_brl' => 241848,
                'max_pages' => 320000,
                'max_projects' => 50,
                'has_advanced_features' => true,
                'permite_imagens' => true,
                'permite_videos' => true,
                'permite_noticias' => true,
                'permite_mobile' => true,
                'permite_compactacao' => true,
                'permite_cache_crawler' => true,
                'permite_padroes_exclusao' => true,
                'permite_politicas_crawl' => true,
                'update_frequency' => 'diario',
                'ideal_for' => 'Agencias, varios clientes e alto volume',
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'price_monthly_usd' => 14999,
                'price_yearly_usd' => 125988,
                'price_monthly_brl' => 89990,
                'price_yearly_brl' => 755928,
                'max_pages' => 1600000,
                'max_projects' => 100,
                'has_advanced_features' => true,
                'permite_imagens' => true,
                'permite_videos' => true,
                'permite_noticias' => true,
                'permite_mobile' => true,
                'permite_compactacao' => true,
                'permite_cache_crawler' => true,
                'permite_padroes_exclusao' => true,
                'permite_politicas_crawl' => true,
                'update_frequency' => 'manual',
                'ideal_for' => 'Operacoes criticas, grandes portais e SLA',
            ],
        ];

        foreach ($planos as $dadosPlano) {
            Plano::updateOrCreate(
                ['slug' => $dadosPlano['slug']],
                $dadosPlano
            );
        }
    }
}
