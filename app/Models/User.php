<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;
use Laravel\Cashier\Billable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'plan_id',
        'role',
        'timezone',
        'ui_preferences',
        'notification_preferences',
        'billing_address',
        'vat_number',
        'api_callback_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ui_preferences' => 'array',
            'notification_preferences' => 'array',
        ];
    }

    public function plano()
    {
        return $this->belongsTo(Plano::class, 'plan_id');
    }

    public function assinaturaPadrao()
    {
        return $this->subscription('default');
    }

    public function estaEmPeriodoTeste(): bool
    {
        return method_exists($this, 'onTrial') ? (bool) $this->onTrial() : false;
    }

    public function possuiAssinaturaVigente(): bool
    {
        $assinatura = $this->assinaturaPadrao();

        return (bool) ($assinatura && $assinatura->valid());
    }

    public function possuiAcessoPagoVigente(): bool
    {
        return $this->possuiAssinaturaVigente() || $this->estaEmPeriodoTeste();
    }

    public function planoGratuito(): ?Plano
    {
        return Plano::query()
            ->where(function ($query) {
                $query->where('slug', 'free')
                    ->orWhereRaw('LOWER(name) = ?', ['free']);
            })
            ->orderBy('id')
            ->first();
    }

    public function planoEhGratuito(?Plano $plano = null): bool
    {
        $plano ??= $this->plano;

        if (!$plano) {
            return true;
        }

        return mb_strtolower((string) $plano->slug) === 'free'
            || mb_strtolower((string) $plano->name) === 'free';
    }

    public function planoExigeAssinatura(?Plano $plano = null): bool
    {
        $plano ??= $this->plano;

        if (!$plano) {
            return false;
        }

        if ($this->planoEhGratuito($plano)) {
            return false;
        }

        return !empty($plano->stripe_monthly_price_id)
            || !empty($plano->stripe_yearly_price_id)
            || !$this->planoEhGratuito($plano);
    }

    public function sincronizarPlanoComAssinatura(): ?Plano
    {
        $this->loadMissing('plano');

        if ($this->isAdmin()) {
            return $this->plano;
        }

        $planoAtual = $this->plano;
        $planoCorreto = $planoAtual;

        if ($this->possuiAcessoPagoVigente()) {
            $idPreco = $this->assinaturaPadrao()?->stripe_price;

            if ($idPreco) {
                $planoPorPreco = Plano::query()
                    ->where('stripe_monthly_price_id', $idPreco)
                    ->orWhere('stripe_yearly_price_id', $idPreco)
                    ->first();

                if ($planoPorPreco) {
                    $planoCorreto = $planoPorPreco;
                }
            }
        } else {
            $planoCorreto = $this->planoGratuito();
        }

        if (($planoAtual?->id) !== ($planoCorreto?->id)) {
            $this->forceFill([
                'plan_id' => $planoCorreto?->id,
            ])->saveQuietly();
        }

        $this->setRelation('plano', $planoCorreto);

        return $planoCorreto;
    }

    public function planoEfetivo(bool $sincronizar = true): ?Plano
    {
        if ($sincronizar) {
            return $this->sincronizarPlanoComAssinatura();
        }

        $this->loadMissing('plano');

        if ($this->isAdmin()) {
            return $this->plano;
        }

        if ($this->planoExigeAssinatura($this->plano) && !$this->possuiAcessoPagoVigente()) {
            return $this->planoGratuito();
        }

        return $this->plano ?: $this->planoGratuito();
    }

    public function projetos()
    {
        return $this->hasMany(Projeto::class);
    }

    public function chavesApi()
    {
        return $this->hasMany(ChaveApi::class);
    }

    public function searchEngineConnections()
    {
        return $this->hasMany(SearchEngineConnection::class);
    }

    public function searchEngineSubmissions()
    {
        return $this->hasMany(SearchEngineSubmission::class);
    }

    /**
     * Helper para saber se é Admin (útil para o futuro)
     */


    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Send the email verification notification.
     * We override this to send our custom WelcomeAndVerifyUser notification
     * with the password reset token, since we combined activation and password creation.
     */
    public function sendEmailVerificationNotification()
    {
        $token = Password::broker()->createToken($this);
        $this->notify(new \App\Notifications\WelcomeAndVerifyUser($token));
    }
}
