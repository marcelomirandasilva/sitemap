<?php

namespace App\Services;

use App\Events\NotificacaoCriada;
use App\Models\Projeto;
use App\Models\SearchEngineSubmission;
use App\Models\Ticket;
use App\Models\TarefaSitemap;
use App\Models\User;
use App\Notifications\NotificacaoSistema;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Support\Str;

class CentralNotificacoesService
{
    public function registrarNotificacao(User $usuario, array $dados): void
    {
        $notificacaoSistema = new NotificacaoSistema($dados);
        $notificacaoSistema->id = (string) Str::uuid();

        $notificacao = app(DatabaseChannel::class)->send(
            $usuario,
            $notificacaoSistema
        );

        if ($notificacao) {
            broadcast(new NotificacaoCriada(
                usuarioId: $usuario->id,
                notificacao: $this->serializar($notificacao)
            ));
        }
    }

    public function preferenciaAtiva(User $usuario, string $chave, bool $padrao = true): bool
    {
        $preferencias = $usuario->notification_preferences ?? [];

        if (!array_key_exists($chave, $preferencias)) {
            return $padrao;
        }

        return (bool) $preferencias[$chave];
    }

    public function notificarCrawler(Projeto $projeto, TarefaSitemap $job): void
    {
        $usuario = $projeto->user;

        if (!$usuario || !$this->preferenciaAtiva($usuario, 'crawler_updates', true)) {
            return;
        }

        $titulo = match ($job->status) {
            'completed' => 'Rastreamento concluido',
            'failed' => 'Rastreamento com falha',
            'cancelled' => 'Rastreamento cancelado',
            default => 'Atualizacao de rastreamento',
        };

        $mensagem = match ($job->status) {
            'completed' => "O projeto {$projeto->name} concluiu o rastreamento com {$job->pages_count} paginas.",
            'failed' => "O projeto {$projeto->name} terminou com falha. {$job->message}",
            'cancelled' => "O rastreamento do projeto {$projeto->name} foi cancelado.",
            default => $job->message,
        };

        $this->registrarNotificacao($usuario, [
            'titulo' => $titulo,
            'mensagem' => trim((string) $mensagem),
            'url' => route('projects.show', $projeto->id),
            'tipo' => 'crawler',
            'categoria' => 'crawler',
            'metadata' => [
                'project_id' => $projeto->id,
                'external_job_id' => $job->external_job_id,
                'status' => $job->status,
            ],
        ]);
    }

    public function notificarEnvioBuscador(Projeto $projeto, SearchEngineSubmission $submissao): void
    {
        $usuario = $projeto->user;

        if (!$usuario || !$this->preferenciaAtiva($usuario, 'search_engine_updates', true)) {
            return;
        }

        $provedor = mb_strtoupper($submissao->provider);
        $titulo = $submissao->status === 'submitted'
            ? "Sitemap enviado ao {$provedor}"
            : "Falha no envio ao {$provedor}";

        $mensagem = $submissao->status === 'submitted'
            ? "O sitemap publico do projeto {$projeto->name} foi enviado ao {$provedor}."
            : "O envio do sitemap do projeto {$projeto->name} para {$provedor} falhou. {$submissao->message}";

        $this->registrarNotificacao($usuario, [
            'titulo' => $titulo,
            'mensagem' => trim((string) $mensagem),
            'url' => route('projects.show', $projeto->id) . '#submit',
            'tipo' => 'buscador',
            'categoria' => 'buscador',
            'metadata' => [
                'project_id' => $projeto->id,
                'submission_id' => $submissao->id,
                'provider' => $submissao->provider,
                'status' => $submissao->status,
            ],
        ]);
    }

    public function notificarRespostaTicket(Ticket $ticket): void
    {
        $usuario = $ticket->usuario;

        if (!$usuario || !$this->preferenciaAtiva($usuario, 'support_updates', true)) {
            return;
        }

        $this->registrarNotificacao($usuario, [
            'titulo' => 'Nova resposta da equipe',
            'mensagem' => "O ticket \"{$ticket->titulo}\" recebeu uma nova resposta.",
            'url' => route('support.show', $ticket->id),
            'tipo' => 'suporte',
            'categoria' => 'suporte',
            'metadata' => [
                'ticket_id' => $ticket->id,
                'status' => $ticket->status,
            ],
        ]);
    }

    public function serializar(DatabaseNotification $notificacao): array
    {
        $dados = $notificacao->data ?? [];

        return [
            'id' => $notificacao->id,
            'titulo' => $dados['titulo'] ?? 'Atualizacao do sistema',
            'mensagem' => $dados['mensagem'] ?? null,
            'url' => $dados['url'] ?? null,
            'tipo' => $dados['tipo'] ?? 'sistema',
            'categoria' => $dados['categoria'] ?? 'geral',
            'lida' => $notificacao->read_at !== null,
            'criada_em' => optional($notificacao->created_at)->toISOString(),
        ];
    }

    public function serializarColecao($colecao): array
    {
        return $colecao
            ->map(fn (DatabaseNotification $notificacao) => $this->serializar($notificacao))
            ->values()
            ->all();
    }
}
