<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sitemap_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            // ID retornado pela API Python (UUID)
            $table->uuid('external_job_id')->unique();

            // Status do job (mapped from API: queued, running, completed, failed)
            $table->string('status')->default('queued');

            // Progresso em porcentagem (0-100)
            $table->float('progress')->default(0);

            // Estatísticas
            $table->integer('pages_count')->default(0);
            $table->integer('images_count')->default(0);
            $table->integer('videos_count')->default(0);

            // JSON com os links dos artefatos gerados (sitemap.xml, images, etc)
            $table->json('artifacts')->nullable();

            // Mensagens de erro ou info
            $table->text('message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Índices para performance
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitemap_jobs');
    }
};
