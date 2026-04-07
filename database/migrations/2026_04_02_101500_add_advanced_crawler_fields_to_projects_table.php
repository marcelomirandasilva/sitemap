<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('check_news')->default(false)->after('check_videos');
            $table->boolean('check_mobile')->default(false)->after('check_news');
            $table->json('exclude_patterns')->nullable()->after('check_mobile');
            $table->string('crawl_policy_id')->nullable()->after('exclude_patterns');
            $table->boolean('compress_output')->default(true)->after('crawl_policy_id');
            $table->boolean('enable_cache')->default(true)->after('compress_output');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'check_news',
                'check_mobile',
                'exclude_patterns',
                'crawl_policy_id',
                'compress_output',
                'enable_cache',
            ]);
        });
    }
};
