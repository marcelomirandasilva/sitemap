<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailSistema extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private array $dados)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->dados['assunto'] ?? $this->dados['titulo'] ?? config('app.name'))
            ->view('emails.sistema', [
                'usuario' => $notifiable,
                'appName' => config('app.name', 'GenMap'),
                'titulo' => $this->dados['titulo'] ?? 'Atualizacao do GenMap',
                'mensagem' => $this->dados['mensagem'] ?? '',
                'linhas' => $this->dados['linhas'] ?? [],
                'acaoTexto' => $this->dados['acao_texto'] ?? null,
                'acaoUrl' => $this->dados['url'] ?? null,
                'rodape' => $this->dados['rodape'] ?? null,
            ]);
    }
}
