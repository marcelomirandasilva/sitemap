<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Remove a tabela incompleta gerada por execução anterior com erro de FK
        Schema::dropIfExists('tickets');

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('projeto_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('titulo');
            $table->text('mensagem');
            $table->enum('status', [
                'aberto',
                'em_analise',
                'em_atendimento',
                'respondido',
                'aguardando_usuario',
                'fechado',
            ])->default('aberto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
