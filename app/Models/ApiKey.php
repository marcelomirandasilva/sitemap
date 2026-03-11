<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Oculta a chave por padrão em serializations (segurança)
    protected $hidden = ['key'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope para chaves ativas e não expiradas
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Gera uma nova API Key única com prefixo sk_live_
     */
    public static function generateKey(): string
    {
        return 'sk_live_' . Str::random(48);
    }

    /**
     * Verifica se a chave está válida (ativa e dentro do prazo)
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Registra o uso da chave (atualiza last_used_at)
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
