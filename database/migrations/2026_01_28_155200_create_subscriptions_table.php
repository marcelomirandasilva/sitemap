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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained(); // Não deletamos planos em uso normalmente, ou usamos restrict

            // Ciclo e Preço (Snapshot do momento da assinatura)
            $table->string('billing_cycle'); // 'monthly', 'yearly'
            $table->decimal('price_paid', 10, 2);
            $table->string('currency', 3)->default('BRL');

            // Estado e Validade
            $table->string('status')->default('active')->index(); // 'active', 'past_due', 'canceled', 'trialing'
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            // Integração Gateway (Híbrido/Genérico)
            $table->string('external_subscription_id')->nullable()->index(); // ID da assinatura no Stripe/Asaas
            $table->string('external_payer_id')->nullable(); // Customer ID no gateway

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
