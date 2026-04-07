<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("UPDATE plans SET update_frequency = 'diario' WHERE update_frequency LIKE '%Di%'");
        DB::statement("UPDATE plans SET update_frequency = 'semanal' WHERE update_frequency LIKE '%Semanal%'");
        DB::statement("UPDATE plans SET update_frequency = 'manual' WHERE update_frequency LIKE '%Manual%'");

        DB::statement("UPDATE plans SET update_frequency = 'diario' WHERE update_frequency NOT IN ('diario', 'semanal', 'quinzenal', 'mensal', 'anual', 'manual') OR update_frequency IS NULL");

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE plans MODIFY update_frequency ENUM('diario', 'semanal', 'quinzenal', 'mensal', 'anual', 'manual') DEFAULT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE plans MODIFY update_frequency VARCHAR(255) DEFAULT NULL");
    }
};
