<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // URLs podem ser longas (até 2048 chars é o padrão seguro para browsers)
            $table->string('url', 2048);

            // Hash para indexação rápida e verificação de unicidade
            $table->string('path_hash')->index();

            $table->integer('status_code')->nullable(); // Ex: 200, 404, 500
            $table->string('title')->nullable();
            $table->float('load_time_ms')->nullable();
            $table->string('content_type')->nullable(); // Ex: text/html
            $table->bigInteger('size_bytes')->nullable();

            $table->timestamps();

            // Garante que não dupliquemos a mesma URL dentro do mesmo projeto
            $table->unique(['project_id', 'path_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};