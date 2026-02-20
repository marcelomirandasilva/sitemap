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

    /**
     * Helper para obter o timezone preferido do usuário
     */
    public function preferredTimezone(): string
    {
        return $this->timezone ?? config('app.timezone');
    }
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
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

