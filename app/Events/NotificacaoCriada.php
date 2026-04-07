<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificacaoCriada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $usuarioId,
        public array $notificacao,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("usuarios.{$this->usuarioId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notificacao.criada';
    }

    public function broadcastWith(): array
    {
        return [
            'notificacao' => $this->notificacao,
        ];
    }
}
