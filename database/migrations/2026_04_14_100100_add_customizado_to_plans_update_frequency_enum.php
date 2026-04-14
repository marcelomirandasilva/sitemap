<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function usaMysqlOuMariaDb(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function tipoAtualColunaFrequencia(): string
    {
        if (!$this->usaMysqlOuMariaDb()) {
            return '';
        }

        $coluna = DB::selectOne("SHOW COLUMNS FROM `plans` LIKE 'update_frequency'");

        return mb_strtolower((string) ($coluna->Type ?? $coluna->type ?? ''));
    }

    private function colunaAceitaCustomizado(): bool
    {
        return str_contains($this->tipoAtualColunaFrequencia(), "'customizado'");
    }

    public function up(): void
    {
        if ($this->usaMysqlOuMariaDb() && !$this->colunaAceitaCustomizado()) {
            DB::statement("ALTER TABLE `plans` CHANGE `update_frequency` `update_frequency` ENUM('diario', 'semanal', 'quinzenal', 'mensal', 'anual', 'manual', 'customizado') NULL DEFAULT NULL");
        }

        if (!$this->usaMysqlOuMariaDb() || $this->colunaAceitaCustomizado()) {
            DB::table('plans')
                ->where('slug', 'enterprise')
                ->update([
                    'update_frequency' => 'customizado',
                ]);

            DB::statement("UPDATE plans SET update_frequency = 'customizado' WHERE LOWER(COALESCE(update_frequency, '')) LIKE '%custom%'");
            DB::statement("UPDATE plans SET update_frequency = 'customizado' WHERE LOWER(COALESCE(update_frequency, '')) LIKE '%person%'");
        }
    }

    public function down(): void
    {
        if (!$this->usaMysqlOuMariaDb() || $this->colunaAceitaCustomizado()) {
            DB::table('plans')
                ->where('update_frequency', 'customizado')
                ->update([
                    'update_frequency' => 'manual',
                ]);
        }

        if (!$this->usaMysqlOuMariaDb()) {
            return;
        }

        DB::statement("ALTER TABLE `plans` CHANGE `update_frequency` `update_frequency` ENUM('diario', 'semanal', 'quinzenal', 'mensal', 'anual', 'manual') NULL DEFAULT NULL");
    }
};
