<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Nome descritivo dado pelo usuário (ex: "Produção", "Teste")
            $table->string('name');

            // A chave em si, prefixada para identificação fácil
            $table->string('key', 64)->unique();

            // Última vez que a chave foi utilizada
            $table->timestamp('last_used_at')->nullable();

            // Data de expiração (null = sem expiração)
            $table->timestamp('expires_at')->nullable();

            // Permite revogar individualmente sem deletar
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Índice para buscas rápidas por key (auth crítica de performance)
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
