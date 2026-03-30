<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sitemap_jobs', function (Blueprint $table) {
            $table->integer('urls_found')->nullable()->after('pages_count');
            $table->integer('urls_crawled')->nullable()->after('urls_found');
            $table->integer('urls_excluded')->nullable()->after('urls_crawled');
        });
    }

    public function down(): void
    {
        Schema::table('sitemap_jobs', function (Blueprint $table) {
            $table->dropColumn(['urls_found', 'urls_crawled', 'urls_excluded']);
        });
    }
};
