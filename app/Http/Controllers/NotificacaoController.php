<?php

namespace App\Http\Controllers;

use App\Services\CentralNotificacoesService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificacaoController extends Controller
{
    public function __construct(protected CentralNotificacoesService $centralNotificacoes)
    {
    }

    public function index(Request $request)
    {
        $usuario = $request->user();

        $notificacoes = $usuario->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn ($notificacao) => $this->centralNotificacoes->serializar($notificacao));

        return Inertia::render('Account/Notifications/Index', [
            'notificacoes' => $notificacoes,
            'nao_lidas' => $usuario->unreadNotifications()->count(),
        ]);
    }

    public function marcarComoLida(Request $request, string $notificacaoId)
    {
        $notificacao = $request->user()->notifications()->whereKey($notificacaoId)->firstOrFail();

        if (!$notificacao->read_at) {
            $notificacao->markAsRead();
        }

        return back()->with('success', 'Notificacao marcada como lida.');
    }

    public function marcarTodasComoLidas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas as notificacoes foram marcadas como lidas.');
    }
}
