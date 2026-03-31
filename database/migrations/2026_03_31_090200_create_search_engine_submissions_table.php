<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_engine_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('site_identifier');
            $table->text('sitemap_url');
            $table->string('status', 32)->default('submitted');
            $table->text('message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'provider']);
            $table->index(['user_id', 'provider']);
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_engine_submissions');
    }
};
