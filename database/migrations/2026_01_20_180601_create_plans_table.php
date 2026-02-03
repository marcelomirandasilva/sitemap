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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('stripe_id')->nullable(); // ID do produto no Stripe
            $table->integer('max_pages'); // Limite de páginas

            // Preços em Centavos (Nullable para permitir planos Free ou customizados)
            $table->integer('price_monthly_brl')->nullable();
            $table->integer('price_yearly_brl')->nullable();
            $table->integer('price_monthly_usd')->nullable();
            $table->integer('price_yearly_usd')->nullable();

            $table->integer('max_projects')->default(10);
            $table->boolean('has_advanced_features')->default(false); // Image Sitemap, Reports, API

            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });
        Schema::dropIfExists('plans');
    }
};
