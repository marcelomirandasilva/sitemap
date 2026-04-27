<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_webhook_stripe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stripe_event_id')->nullable()->unique();
            $table->string('tipo_evento');
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('status_processamento')->default('recebido');
            $table->text('erro_processamento')->nullable();
            $table->timestamp('processado_em')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index(['tipo_evento', 'created_at']);
            $table->index(['stripe_customer_id', 'created_at']);
        });

        Schema::create('pagamentos_stripe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('plano_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('evento_webhook_stripe_id')->nullable()->constrained('eventos_webhook_stripe')->nullOnDelete();
            $table->string('origem')->default('webhook');
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->string('moeda', 10)->nullable();
            $table->unsignedBigInteger('valor_total_centavos')->default(0);
            $table->unsignedBigInteger('valor_pago_centavos')->default(0);
            $table->string('status')->nullable();
            $table->string('descricao')->nullable();
            $table->string('motivo_cobranca')->nullable();
            $table->string('invoice_pdf_url')->nullable();
            $table->string('hosted_invoice_url')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->json('dados')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['stripe_customer_id', 'created_at']);
            $table->index(['stripe_subscription_id', 'created_at']);
        });

        Schema::create('movimentacoes_assinatura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('plano_origem_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('plano_destino_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('evento_webhook_stripe_id')->nullable()->constrained('eventos_webhook_stripe')->nullOnDelete();
            $table->string('origem')->default('webhook');
            $table->string('tipo_movimentacao');
            $table->string('status')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_event_id')->nullable()->unique();
            $table->text('descricao')->nullable();
            $table->json('dados')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['stripe_subscription_id', 'created_at']);
            $table->index(['tipo_movimentacao', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_assinatura');
        Schema::dropIfExists('pagamentos_stripe');
        Schema::dropIfExists('eventos_webhook_stripe');
    }
};
