<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url');
            $table->string('user_agent_custom')->nullable();
            $table->float('delay_between_requests')->default(1.0);
            $table->integer('max_concurrent_requests')->default(2);
            $table->string('current_crawler_job_id')->nullable()->index();
            $table->integer('max_depth')->default(3);
            $table->integer('max_pages')->default(1000);
            $table->boolean('check_images')->default(false);
            $table->boolean('check_videos')->default(false);
            $table->string('frequency')->default('manual');
            $table->timestamp('last_crawled_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
