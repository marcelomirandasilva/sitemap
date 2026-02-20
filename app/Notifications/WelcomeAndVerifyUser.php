<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class WelcomeAndVerifyUser extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $activationUrl = $this->activationUrl($notifiable);

        return (new MailMessage)
            ->subject('Link de ativação para sua conta ' . config('app.name'))
            ->view('emails.welcome-verify', [
                'user' => $notifiable,
                'activationUrl' => $activationUrl,
                'appName' => config('app.name')
            ]);
    }

    /**
     * Get the activation URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function activationUrl($notifiable)
    {
        return url('/activate/' . $this->token . '?email=' . urlencode($notifiable->getEmailForVerification()));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
