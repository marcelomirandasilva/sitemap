<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('permite_noticias')->default(false)->after('permite_videos');
            $table->boolean('permite_mobile')->default(false)->after('permite_noticias');
            $table->boolean('permite_compactacao')->default(false)->after('permite_mobile');
            $table->boolean('permite_cache_crawler')->default(false)->after('permite_compactacao');
            $table->boolean('permite_padroes_exclusao')->default(false)->after('permite_cache_crawler');
            $table->boolean('permite_politicas_crawl')->default(false)->after('permite_padroes_exclusao');
        });

        DB::table('plans')
            ->where('has_advanced_features', true)
            ->update([
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
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'permite_noticias',
                'permite_mobile',
                'permite_compactacao',
                'permite_cache_crawler',
                'permite_padroes_exclusao',
                'permite_politicas_crawl',
            ]);
        });
    }
};
