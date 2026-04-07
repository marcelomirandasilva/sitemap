<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AnexoTicket;
use App\Models\Projeto;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TicketController extends Controller
{
    /**
     * Exibe a lista de tickets do usuário autenticado.
     */
    public function index()
    {
        $tickets = Ticket::where('user_id', auth()->id())
            ->with(['projeto:id,name'])
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'titulo' => $t->titulo,
                'status' => $t->status,
                'projeto' => $t->projeto?->name,
                'criado_em' => $t->created_at->toIso8601String(),
                'respostas' => $t->respostas()->count(),
            ]);

        $projetos = Projeto::where('user_id', auth()->id())
            ->select('id', 'name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'nome' => $p->name]);

        return Inertia::render('Support/Index', [
            'tickets' => $tickets,
            'projetos' => $projetos,
        ]);
    }

    /**
     * Armazena um novo ticket com anexos opcionais.
     */
    public function store(Request $request)
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'projeto_id' => ['nullable', 'integer', 'exists:projects,id'],
            'mensagem' => ['required', 'string', 'min:10'],
            'anexos' => ['nullable', 'array', 'max:5'],
            'anexos.*' => ['file', 'mimes:jpeg,jpg,png', 'max:5120'],
        ]);

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'projeto_id' => $dados['projeto_id'] ?? null,
            'titulo' => $dados['titulo'],
            'mensagem' => $dados['mensagem'],
            'status' => Ticket::STATUS_ABERTO,
        ]);

        // Processar anexos, se houver
        if ($request->hasFile('anexos')) {
            foreach ($request->file('anexos') as $arquivo) {
                $caminho = $arquivo->store('tickets/' . $ticket->id, 'public');

                AnexoTicket::create([
                    'ticket_id' => $ticket->id,
                    'caminho_arquivo' => $caminho,
                    'nome_original' => $arquivo->getClientOriginalName(),
                ]);
            }
        }

        return redirect()->route('support.index')
            ->with('success', 'Ticket criado com sucesso!');
    }

    /**
     * Exibe um ticket específico com suas respostas.
     * Apenas o dono do ticket pode visualizá-lo.
     */
    public function show(Ticket $ticket)
    {
        // Apenas o dono do ticket ou admin pode visualizá-lo
        abort_if(
            $ticket->user_id !== auth()->id() && !auth()->user()->isAdmin(),
            403,
            'Acesso não autorizado.'
        );

        $ticket->load([
            'projeto:id,name',
            'respostas.usuario:id,name',
            'anexos',
        ]);

        return Inertia::render('Support/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'titulo' => $ticket->titulo,
                'mensagem' => $ticket->mensagem,
                'status' => $ticket->status,
                'projeto' => $ticket->projeto?->name,
                'criado_em' => $ticket->created_at->toIso8601String(),
                'respostas' => $ticket->respostas->map(fn($r) => [
                    'id' => $r->id,
                    'mensagem' => $r->mensagem,
                    'is_admin' => $r->is_admin,
                    'autor' => $r->usuario->name,
                    'criado_em' => $r->created_at->toIso8601String(),
                ]),
                'anexos' => $ticket->anexos->map(fn($a) => [
                    'id' => $a->id,
                    'nome_original' => $a->nome_original,
                    'url' => Storage::url($a->caminho_arquivo),
                ]),
            ],
        ]);
    }
    /**
     * Adiciona uma resposta do usuário ao ticket.
     */
    public function reply(Request $request, Ticket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403, 'Acesso não autorizado.');
        abort_if($ticket->status === Ticket::STATUS_FECHADO, 403, 'Ticket já encerrado.');

        $dados = $request->validate([
            'mensagem' => ['required', 'string', 'min:5'],
            'anexos' => ['nullable', 'array', 'max:5'],
            'anexos.*' => ['file', 'mimes:jpeg,jpg,png', 'max:5120'],
        ]);

        \App\Models\RespostaTicket::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'mensagem' => $dados['mensagem'],
            'is_admin' => false,
        ]);

        // Processar novos anexos, se houver
        if ($request->hasFile('anexos')) {
            foreach ($request->file('anexos') as $arquivo) {
                $caminho = $arquivo->store('tickets/' . $ticket->id, 'public');
                \App\Models\AnexoTicket::create([
                    'ticket_id' => $ticket->id,
                    'caminho_arquivo' => $caminho,
                    'nome_original' => $arquivo->getClientOriginalName(),
                ]);
            }
        }

        // Atualiza o status para em análise (aguardando resposta da equipe)
        $ticket->update(['status' => Ticket::STATUS_EM_ANALISE]);

        return redirect()->route('support.show', $ticket->id)
            ->with('success', 'Informações adicionadas com sucesso!');
    }

    /**
     * Fecha o ticket (marcado como resolvido pelo usuário).
     */
    public function fechar(Ticket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403, 'Acesso não autorizado.');

        $ticket->update(['status' => Ticket::STATUS_FECHADO]);

        return redirect()->route('support.index')
            ->with('success', 'Ticket encerrado com sucesso.');
    }
}
