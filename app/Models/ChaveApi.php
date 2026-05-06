<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ChaveApi extends Model
{
    use HasFactory;

    protected $table = 'api_keys';

    protected $fillable = [
        'user_id',
        'name',
        'key_hash',
        'key_preview',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['key_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public static function gerarChave(): string
    {
        return 'sk_live_' . Str::random(48);
    }

    public static function hashChave(string $chave): string
    {
        return hash('sha256', $chave);
    }

    public static function previewDaChave(string $chave): string
    {
        return 'sk_live_...' . substr($chave, -6);
    }

    public static function atributosPersistencia(string $chave): array
    {
        return [
            'key_hash' => self::hashChave($chave),
            'key_preview' => self::previewDaChave($chave),
        ];
    }

    public function estaValida(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function correspondeA(string $chave): bool
    {
        if (!$this->key_hash) {
            return false;
        }

        return hash_equals($this->key_hash, self::hashChave($chave));
    }

    public function marcarUso(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
