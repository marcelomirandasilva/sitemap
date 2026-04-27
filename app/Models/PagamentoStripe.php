<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagamentoStripe extends Model
{
    protected $table = 'pagamentos_stripe';

    protected $fillable = [
        'user_id',
        'plano_id',
        'evento_webhook_stripe_id',
        'origem',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'moeda',
        'valor_total_centavos',
        'valor_pago_centavos',
        'status',
        'descricao',
        'motivo_cobranca',
        'invoice_pdf_url',
        'hosted_invoice_url',
        'pago_em',
        'dados',
    ];

    protected $casts = [
        'valor_total_centavos' => 'integer',
        'valor_pago_centavos' => 'integer',
        'pago_em' => 'datetime',
        'dados' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plano_id');
    }

    public function eventoWebhookStripe()
    {
        return $this->belongsTo(EventoWebhookStripe::class, 'evento_webhook_stripe_id');
    }
}
