<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anexos_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('caminho_arquivo');
            $table->string('nome_original');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos_tickets');
    }
};
