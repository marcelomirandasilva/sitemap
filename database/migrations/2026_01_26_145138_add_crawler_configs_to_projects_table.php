<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('user_agent_custom')->nullable()->after('url');
            $table->integer('max_depth')->default(3)->after('user_agent_custom');
            $table->integer('max_pages')->default(1000)->after('max_depth');
            $table->boolean('check_images')->default(false)->after('max_pages');
            $table->boolean('check_videos')->default(false)->after('check_images');
            $table->timestamp('last_crawled_at')->nullable()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'user_agent_custom',
                'max_depth',
                'max_pages',
                'check_images',
                'check_videos',
                'last_crawled_at'
            ]);
        });
    }
};