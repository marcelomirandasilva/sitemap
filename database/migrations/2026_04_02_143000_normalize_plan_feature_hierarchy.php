<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('plans')
            ->where('slug', 'free')
            ->update([
                'permite_imagens' => true,
                'permite_videos' => true,
                'update_frequency' => 'manual',
                'ideal_for' => 'Testar o servico em um unico site',
            ]);

        DB::table('plans')
            ->where('slug', 'solo')
            ->update([
                'permite_imagens' => true,
                'permite_videos' => true,
                'update_frequency' => 'semanal',
                'ideal_for' => 'Operar ate 3 sites com atualizacao semanal e midia',
            ]);

        DB::table('plans')
            ->whereIn('slug', ['growth', 'pro', 'scale', 'enterprise'])
            ->update([
                'permite_imagens' => true,
                'permite_videos' => true,
                'permite_noticias' => true,
                'permite_mobile' => true,
                'permite_compactacao' => true,
                'permite_cache_crawler' => true,
                'permite_padroes_exclusao' => true,
                'permite_politicas_crawl' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migracao de normalizacao de dados. Sem reversao automatica.
    }
};
