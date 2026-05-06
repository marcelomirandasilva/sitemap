<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AnexoTicket;
use App\Models\Projeto;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TicketController extends Controller
{
    protected function authorizeTicketAccess(Ticket $ticket): void
    {
        abort_if(
            $ticket->user_id !== auth()->id() && !auth()->user()->isAdmin(),
            403,
            'Acesso nao autorizado.'
        );
    }

    protected function storeAttachment(Ticket $ticket, UploadedFile $arquivo): void
    {
        $caminho = $arquivo->store('tickets/' . $ticket->id, 'local');

        AnexoTicket::create([
            'ticket_id' => $ticket->id,
            'caminho_arquivo' => $caminho,
            'nome_original' => $arquivo->getClientOriginalName(),
        ]);
    }

    protected function downloadAttachmentFromDisk(AnexoTicket $anexo)
    {
        if (Storage::disk('local')->exists($anexo->caminho_arquivo)) {
            $conteudo = Storage::disk('local')->get($anexo->caminho_arquivo);
            $mimeType = Storage::disk('local')->mimeType($anexo->caminho_arquivo) ?: 'application/octet-stream';

            return response($conteudo, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . addslashes($anexo->nome_original) . '"',
            ]);
        }

        if (Storage::disk('public')->exists($anexo->caminho_arquivo)) {
            $conteudo = Storage::disk('public')->get($anexo->caminho_arquivo);
            Storage::disk('local')->put($anexo->caminho_arquivo, $conteudo);
            Storage::disk('public')->delete($anexo->caminho_arquivo);
            $mimeType = Storage::disk('local')->mimeType($anexo->caminho_arquivo) ?: 'application/octet-stream';

            return response($conteudo, 200, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . addslashes($anexo->nome_original) . '"',
            ]);
        }

        abort(404, 'Anexo nao encontrado.');
    }

    public function index()
    {
        $tickets = Ticket::where('user_id', auth()->id())
            ->with(['projeto:id,name'])
            ->latest()
            ->get()
            ->map(fn ($t) => [
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
            ->map(fn ($p) => ['id' => $p->id, 'nome' => $p->name]);

        return Inertia::render('Support/Index', [
            'tickets' => $tickets,
            'projetos' => $projetos,
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'projeto_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id());
                }),
            ],
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

        if ($request->hasFile('anexos')) {
            foreach ($request->file('anexos') as $arquivo) {
                $this->storeAttachment($ticket, $arquivo);
            }
        }

        return redirect()->route('support.index')
            ->with('success', 'Ticket criado com sucesso!');
    }

    public function show(Ticket $ticket)
    {
        $this->authorizeTicketAccess($ticket);

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
                'respostas' => $ticket->respostas->map(fn ($r) => [
                    'id' => $r->id,
                    'mensagem' => $r->mensagem,
                    'is_admin' => $r->is_admin,
                    'autor' => $r->usuario->name,
                    'criado_em' => $r->created_at->toIso8601String(),
                ]),
                'anexos' => $ticket->anexos->map(fn ($a) => [
                    'id' => $a->id,
                    'nome_original' => $a->nome_original,
                    'url' => route('support.attachments.download', [
                        'ticket' => $ticket->id,
                        'anexo' => $a->id,
                    ]),
                ]),
            ],
        ]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403, 'Acesso nao autorizado.');
        abort_if($ticket->status === Ticket::STATUS_FECHADO, 403, 'Ticket ja encerrado.');

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

        if ($request->hasFile('anexos')) {
            foreach ($request->file('anexos') as $arquivo) {
                $this->storeAttachment($ticket, $arquivo);
            }
        }

        $ticket->update(['status' => Ticket::STATUS_EM_ANALISE]);

        return redirect()->route('support.show', $ticket->id)
            ->with('success', 'Informacoes adicionadas com sucesso!');
    }

    public function fechar(Ticket $ticket)
    {
        abort_if($ticket->user_id !== auth()->id(), 403, 'Acesso nao autorizado.');

        $ticket->update(['status' => Ticket::STATUS_FECHADO]);

        return redirect()->route('support.index')
            ->with('success', 'Ticket encerrado com sucesso.');
    }

    public function downloadAttachment(Ticket $ticket, AnexoTicket $anexo)
    {
        $this->authorizeTicketAccess($ticket);

        if ($anexo->ticket_id !== $ticket->id) {
            abort(404);
        }

        return $this->downloadAttachmentFromDisk($anexo);
    }
}
