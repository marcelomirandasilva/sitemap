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
            if (Schema::hasColumn('plans', 'stripe_id')) {
                $table->dropColumn('stripe_id');
            }

            if (!Schema::hasColumn('plans', 'stripe_monthly_price_id')) {
                $table->string('stripe_monthly_price_id')->nullable()->after('slug');
                $table->index('stripe_monthly_price_id');
            }

            if (!Schema::hasColumn('plans', 'stripe_yearly_price_id')) {
                $table->string('stripe_yearly_price_id')->nullable()->after('stripe_monthly_price_id');
                $table->index('stripe_yearly_price_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'stripe_monthly_price_id')) {
                $table->dropColumn(['stripe_monthly_price_id', 'stripe_yearly_price_id']);
            }

            if (!Schema::hasColumn('plans', 'stripe_id')) {
                $table->string('stripe_id')->nullable()->after('slug');
            }
        });
    }
};
