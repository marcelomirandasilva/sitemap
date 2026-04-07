<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('language', 20)->nullable()->after('size_bytes');
            $table->text('meta_description')->nullable()->after('language');
            $table->string('canonical_url', 2048)->nullable()->after('meta_description');
            $table->json('hreflang_links')->nullable()->after('canonical_url');

            $table->index(['project_id', 'language'], 'pages_project_language_index');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_project_language_index');
            $table->dropColumn([
                'language',
                'meta_description',
                'canonical_url',
                'hreflang_links',
            ]);
        });
    }
};
