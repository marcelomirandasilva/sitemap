<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\CentralNotificacoesService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function __construct(protected CentralNotificacoesService $centralNotificacoes)
    {
    }

    public function index(Request $request)
    {
        $query = Ticket::with(['usuario', 'projeto'])->orderBy('updated_at', 'desc');

        if ($request->filled('status') && $request->status !== 'todos') {
            $query->where('status', $request->status);
        }

        $tickets = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status'])
        ]);
    }

    public function show(Ticket $ticket)
    {
        // Se um admin clica num ticket 'novo', atualizamos passivamente para aberto (em analise) se quisermos.
        // Pelo Filament, era uma label solta, vamos manter a lógica inalterada.
        $ticket->load(['usuario', 'projeto', 'respostas.usuario', 'anexos']);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $ticket
        ]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate([
            'mensagem' => 'required|string'
        ]);

        $ticket->respostas()->create([
            'user_id' => auth()->id(),
            'mensagem' => $request->mensagem,
            'is_admin' => true,
        ]);

        // Atualiza a flag do ticket pai para que o usuário veja
        $ticket->update(['status' => 'respondido']);
        $ticket->loadMissing('usuario');
        $this->centralNotificacoes->notificarRespostaTicket($ticket);

        return back()->with('success', 'Resposta do Administrador enviada.');
    }

    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:aberto,respondido,fechado'
        ]);

        $ticket->update(['status' => $request->status]);

        return back()->with('success', 'Status do suporte atualizado.');
    }
}
