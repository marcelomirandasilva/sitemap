<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NotificacaoSistema extends Notification
{
    use Queueable;

    public function __construct(protected array $dados)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => $this->dados['titulo'] ?? 'Atualizacao do sistema',
            'mensagem' => $this->dados['mensagem'] ?? null,
            'url' => $this->dados['url'] ?? null,
            'tipo' => $this->dados['tipo'] ?? 'sistema',
            'categoria' => $this->dados['categoria'] ?? 'geral',
            'metadata' => $this->dados['metadata'] ?? [],
        ];
    }
}
