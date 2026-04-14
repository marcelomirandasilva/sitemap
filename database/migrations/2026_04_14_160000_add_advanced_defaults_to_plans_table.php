<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('profundidade_maxima_padrao')->default(3)->after('max_projects');
            $table->unsignedInteger('profundidade_maxima_limite')->default(3)->after('profundidade_maxima_padrao');
            $table->unsignedInteger('concorrencia_padrao')->default(2)->after('profundidade_maxima_limite');
            $table->unsignedInteger('concorrencia_limite')->default(2)->after('concorrencia_padrao');
            $table->decimal('atraso_padrao_segundos', 6, 2)->default(1.00)->after('concorrencia_limite');
            $table->decimal('atraso_minimo_segundos', 6, 2)->default(1.00)->after('atraso_padrao_segundos');
            $table->decimal('atraso_maximo_segundos', 6, 2)->default(1.00)->after('atraso_minimo_segundos');
            $table->unsignedInteger('intervalo_personalizado_padrao_horas')->default(24)->after('update_frequency');
        });

        $valoresPorPlano = [
            'free' => [
                'profundidade_maxima_padrao' => 3,
                'profundidade_maxima_limite' => 3,
                'concorrencia_padrao' => 2,
                'concorrencia_limite' => 2,
                'atraso_padrao_segundos' => 1.00,
                'atraso_minimo_segundos' => 1.00,
                'atraso_maximo_segundos' => 1.00,
                'intervalo_personalizado_padrao_horas' => 24,
            ],
            'solo' => [
                'profundidade_maxima_padrao' => 3,
                'profundidade_maxima_limite' => 3,
                'concorrencia_padrao' => 2,
                'concorrencia_limite' => 2,
                'atraso_padrao_segundos' => 1.00,
                'atraso_minimo_segundos' => 1.00,
                'atraso_maximo_segundos' => 1.00,
                'intervalo_personalizado_padrao_horas' => 24,
            ],
            'growth' => [
                'profundidade_maxima_padrao' => 4,
                'profundidade_maxima_limite' => 6,
                'concorrencia_padrao' => 4,
                'concorrencia_limite' => 10,
                'atraso_padrao_segundos' => 0.80,
                'atraso_minimo_segundos' => 0.50,
                'atraso_maximo_segundos' => 5.00,
                'intervalo_personalizado_padrao_horas' => 24,
            ],
            'pro' => [
                'profundidade_maxima_padrao' => 5,
                'profundidade_maxima_limite' => 8,
                'concorrencia_padrao' => 8,
                'concorrencia_limite' => 25,
                'atraso_padrao_segundos' => 0.50,
                'atraso_minimo_segundos' => 0.20,
                'atraso_maximo_segundos' => 5.00,
                'intervalo_personalizado_padrao_horas' => 24,
            ],
            'scale' => [
                'profundidade_maxima_padrao' => 7,
                'profundidade_maxima_limite' => 10,
                'concorrencia_padrao' => 15,
                'concorrencia_limite' => 50,
                'atraso_padrao_segundos' => 0.30,
                'atraso_minimo_segundos' => 0.10,
                'atraso_maximo_segundos' => 5.00,
                'intervalo_personalizado_padrao_horas' => 24,
            ],
            'enterprise' => [
                'profundidade_maxima_padrao' => 10,
                'profundidade_maxima_limite' => 10,
                'concorrencia_padrao' => 30,
                'concorrencia_limite' => 100,
                'atraso_padrao_segundos' => 0.10,
                'atraso_minimo_segundos' => 0.10,
                'atraso_maximo_segundos' => 10.00,
                'intervalo_personalizado_padrao_horas' => 24,
            ],
        ];

        foreach ($valoresPorPlano as $slug => $valores) {
            DB::table('plans')->where('slug', $slug)->update($valores);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'profundidade_maxima_padrao',
                'profundidade_maxima_limite',
                'concorrencia_padrao',
                'concorrencia_limite',
                'atraso_padrao_segundos',
                'atraso_minimo_segundos',
                'atraso_maximo_segundos',
                'intervalo_personalizado_padrao_horas',
            ]);
        });
    }
};
