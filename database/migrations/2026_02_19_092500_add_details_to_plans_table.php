<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'update_frequency')) {
                $table->string('update_frequency')->nullable()->after('has_advanced_features');
            }
            if (!Schema::hasColumn('plans', 'ideal_for')) {
                $table->string('ideal_for')->nullable()->after('update_frequency');
            }

            // Novos campos de preços (em centavos)
            if (!Schema::hasColumn('plans', 'price_monthly_brl')) {
                $table->integer('price_monthly_brl')->default(0)->after('ideal_for');
            }
            if (!Schema::hasColumn('plans', 'price_yearly_brl')) {
                $table->integer('price_yearly_brl')->default(0)->after('price_monthly_brl');
            }
            if (!Schema::hasColumn('plans', 'price_monthly_usd')) {
                $table->integer('price_monthly_usd')->default(0)->after('price_yearly_brl');
            }
            if (!Schema::hasColumn('plans', 'price_yearly_usd')) {
                $table->integer('price_yearly_usd')->default(0)->after('price_monthly_usd');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['update_frequency', 'ideal_for']);
        });
    }
};
