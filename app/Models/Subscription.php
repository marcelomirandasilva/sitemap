<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'billing_cycle', // monthly, yearly
        'price_paid',
        'currency',
        'status', // active, past_due, canceled, trialing
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'canceled_at',
        'external_subscription_id',
        'external_payer_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'price_paid' => 'decimal:2',
    ];

    // --- Relacionamentos ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // --- Métodos Auxiliares ---

    public function isActive()
    {
        return in_array($this->status, ['active', 'trialing']) &&
            ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isCanceled()
    {
        return $this->canceled_at !== null;
    }

    public function isTrial()
    {
        return $this->status === 'trialing' && $this->trial_ends_at?->isFuture();
    }
}
