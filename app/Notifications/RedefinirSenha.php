<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RedefinirSenha extends Notification implements ShouldQueue
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
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Redefinir senha do GenMap')
            ->view('emails.sistema', [
                'usuario' => $notifiable,
                'appName' => config('app.name'),
                'titulo' => 'Redefinicao de senha',
                'mensagem' => 'Recebemos uma solicitacao para redefinir a senha da sua conta.',
                'linhas' => [
                    'Se voce solicitou a redefinicao, clique no botao abaixo.',
                    'Se voce nao solicitou, ignore este e-mail.',
                ],
                'acaoTexto' => 'Redefinir senha',
                'acaoUrl' => $url,
                'rodape' => 'Por seguranca, este link expira automaticamente.',
            ]);
    }
}
