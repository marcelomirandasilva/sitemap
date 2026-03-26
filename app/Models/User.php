<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;
use Laravel\Cashier\Billable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
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

    public function projetos()
    {
        return $this->hasMany(Projeto::class);
    }

    public function chavesApi()
    {
        return $this->hasMany(ChaveApi::class);
    }

    /**
     * Helper para saber se é Admin (útil para o futuro)
     */


    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
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

