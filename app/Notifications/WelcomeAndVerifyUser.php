<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeAndVerifyUser extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urlAtivacao = url('/activate/' . $this->token . '?email=' . urlencode($notifiable->getEmailForVerification()));

        return (new MailMessage)
            ->subject('Ative sua conta ' . config('app.name'))
            ->view('emails.sistema', [
                'usuario' => $notifiable,
                'appName' => config('app.name'),
                'titulo' => 'Sua conta GenMap foi criada',
                'mensagem' => 'Confirme seu endereco de e-mail para ativar sua conta e comecar a gerar sitemaps.',
                'linhas' => [
                    'E-mail da conta: ' . $notifiable->email,
                    'Apos a ativacao, voce podera adicionar seu primeiro site e iniciar o rastreamento.',
                ],
                'acaoTexto' => 'Ativar conta',
                'acaoUrl' => $urlAtivacao,
                'rodape' => 'Se voce nao criou esta conta, ignore este e-mail.',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
