<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // A página ONDE o link foi encontrado
            $table->foreignId('source_page_id')->constrained('pages')->cascadeOnDelete();

            $table->string('target_url', 2048);
            $table->boolean('is_external')->default(false);
            $table->boolean('is_broken')->default(false);
            $table->string('anchor_text')->nullable();

            $table->timestamps();

            // Index para facilitar queries como "Mostre todos os links quebrados deste projeto"
            $table->index(['project_id', 'is_broken']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};