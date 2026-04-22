<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SenhaAlterada extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private ?string $ip = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Sua senha do GenMap foi alterada')
            ->view('emails.sistema', [
                'usuario' => $notifiable,
                'appName' => config('app.name'),
                'titulo' => 'Senha alterada',
                'mensagem' => 'A senha da sua conta foi alterada com sucesso.',
                'linhas' => array_filter([
                    $this->ip ? 'IP da solicitacao: ' . $this->ip : null,
                    'Se foi voce, nenhuma acao adicional e necessaria.',
                    'Se voce nao reconhece esta alteracao, redefina sua senha imediatamente.',
                ]),
                'acaoTexto' => 'Abrir conta',
                'acaoUrl' => route('preferences.edit'),
                'rodape' => 'Este e-mail de seguranca nao pode ser desativado nas preferencias.',
            ]);
    }
}
