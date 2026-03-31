<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('published_sitemap_url')->nullable()->after('url');
            $table->string('google_site_property')->nullable()->after('published_sitemap_url');
            $table->text('bing_site_url')->nullable()->after('google_site_property');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'published_sitemap_url',
                'google_site_property',
                'bing_site_url',
            ]);
        });
    }
};
