<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('key_hash', 64)->nullable()->after('name');
            $table->string('key_preview', 32)->nullable()->after('key_hash');
            $table->string('key', 64)->nullable()->change();
        });

        DB::table('api_keys')
            ->orderBy('id')
            ->get(['id', 'key'])
            ->each(function ($registro): void {
                $chave = $registro->key;

                if (!is_string($chave) || $chave === '') {
                    return;
                }

                DB::table('api_keys')
                    ->where('id', $registro->id)
                    ->update([
                        'key_hash' => hash('sha256', $chave),
                        'key_preview' => 'sk_live_...' . substr($chave, -6),
                        'key' => null,
                    ]);
            });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->unique('key_hash');
        });
    }

    public function down(): void
    {
        DB::table('api_keys')
            ->whereNull('key')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($registro): void {
                DB::table('api_keys')
                    ->where('id', $registro->id)
                    ->update([
                        'key' => 'revoked_' . $registro->id . '_' . substr(hash('sha256', (string) $registro->id), 0, 16),
                    ]);
            });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropUnique('api_keys_key_hash_unique');
            $table->dropColumn(['key_hash', 'key_preview']);
            $table->string('key', 64)->nullable(false)->change();
        });
    }
};
