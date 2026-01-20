<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Garante que não duplica se rodar 2x
        DB::table('plans')->upsert([
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
                'max_projects' => 1,
                'max_pages_per_sitemap' => 500,
                'has_image_sitemap' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price' => 2990, // R$ 29,90
                'max_projects' => 10,
                'max_pages_per_sitemap' => 10000,
                'has_image_sitemap' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ], ['slug']);
    }
}