<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNewUser extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $password)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bem-vindo ao ' . config('app.name'))
            ->view('emails.sistema', [
                'usuario' => $notifiable,
                'appName' => config('app.name'),
                'titulo' => 'Bem-vindo ao GenMap',
                'mensagem' => 'Sua conta foi criada e ja esta pronta para acesso.',
                'linhas' => [
                    'E-mail: ' . $notifiable->email,
                    'Senha temporaria: ' . $this->password,
                    'Recomendamos alterar a senha no primeiro acesso.',
                ],
                'acaoTexto' => 'Acessar painel',
                'acaoUrl' => route('login'),
                'rodape' => 'Mantenha esta mensagem em seguranca e altere sua senha no primeiro acesso.',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
