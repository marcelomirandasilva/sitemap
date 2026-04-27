<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoWebhookStripe extends Model
{
    protected $table = 'eventos_webhook_stripe';

    protected $fillable = [
        'user_id',
        'stripe_event_id',
        'tipo_evento',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'status_processamento',
        'erro_processamento',
        'processado_em',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'processado_em' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
