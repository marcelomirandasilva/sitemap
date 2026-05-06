<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('anexos_tickets')) {
            return;
        }

        DB::table('anexos_tickets')
            ->orderBy('id')
            ->get(['caminho_arquivo'])
            ->each(function ($anexo): void {
                $caminho = (string) $anexo->caminho_arquivo;

                if ($caminho === '') {
                    return;
                }

                if (Storage::disk('public')->exists($caminho) && !Storage::disk('local')->exists($caminho)) {
                    Storage::disk('local')->put($caminho, Storage::disk('public')->get($caminho));
                }

                if (Storage::disk('public')->exists($caminho)) {
                    Storage::disk('public')->delete($caminho);
                }
            });
    }

    public function down(): void
    {
        // Data migration intentionally not reverted automatically.
    }
};
