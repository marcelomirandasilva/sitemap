<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Higienizar os dados preexistentes
        DB::statement("UPDATE plans SET update_frequency = 'diario' WHERE update_frequency LIKE '%Diária%';");
        DB::statement("UPDATE plans SET update_frequency = 'semanal' WHERE update_frequency LIKE '%Semanal%';");
        DB::statement("UPDATE plans SET update_frequency = 'manual' WHERE update_frequency LIKE '%Manual%';");

        // Qualquer outro lixo (ex: 'Custom', 'Diária + delta crawl') cai pro 'diario' como default pra não quebrar
        DB::statement("UPDATE plans SET update_frequency = 'diario' WHERE update_frequency NOT IN ('diario', 'semanal', 'quinzenal', 'mensal', 'anual', 'manual') OR update_frequency IS NULL;");

        // 2. Transmutar o formato da coluna
        DB::statement("ALTER TABLE plans MODIFY update_frequency ENUM('diario', 'semanal', 'quinzenal', 'mensal', 'anual', 'manual') DEFAULT NULL;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE plans MODIFY update_frequency VARCHAR(255) DEFAULT NULL;");
    }
};
