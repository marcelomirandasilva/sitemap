<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimentacaoAssinatura extends Model
{
    protected $table = 'movimentacoes_assinatura';

    protected $fillable = [
        'user_id',
        'plano_origem_id',
        'plano_destino_id',
        'evento_webhook_stripe_id',
        'origem',
        'tipo_movimentacao',
        'status',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'stripe_event_id',
        'descricao',
        'dados',
    ];

    protected $casts = [
        'dados' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function planoOrigem()
    {
        return $this->belongsTo(Plano::class, 'plano_origem_id');
    }

    public function planoDestino()
    {
        return $this->belongsTo(Plano::class, 'plano_destino_id');
    }

    public function eventoWebhookStripe()
    {
        return $this->belongsTo(EventoWebhookStripe::class, 'evento_webhook_stripe_id');
    }
}
