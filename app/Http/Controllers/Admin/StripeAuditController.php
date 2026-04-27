<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventoWebhookStripe;
use App\Models\MovimentacaoAssinatura;
use App\Models\PagamentoStripe;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class StripeAuditController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->get('search', ''));

        $movimentacoes = MovimentacaoAssinatura::query()
            ->with(['usuario', 'planoOrigem', 'planoDestino'])
            ->latest('id');

        $pagamentos = PagamentoStripe::query()
            ->with(['usuario', 'plano'])
            ->latest('pago_em')
            ->latest('id');

        $eventos = EventoWebhookStripe::query()
            ->with('usuario')
            ->latest('id');

        if ($busca !== '') {
            $this->aplicarBuscaMovimentacoes($movimentacoes, $busca);
            $this->aplicarBuscaPagamentos($pagamentos, $busca);
            $this->aplicarBuscaEventos($eventos, $busca);
        }

        $movimentacoes = $movimentacoes->limit(200)->get();
        $pagamentos = $pagamentos->limit(200)->get();
        $eventos = $eventos->limit(200)->get();

        $processos = $this->montarProcessos($movimentacoes, $pagamentos, $eventos);

        $estatisticas = [
            'total_processos' => $processos->count(),
            'processos_com_erro' => $processos->where('status_resumo', 'erro')->count(),
            'processos_concluidos' => $processos->where('status_resumo', 'concluido')->count(),
            'pagamentos_registrados' => $pagamentos->count(),
        ];

        return Inertia::render('Admin/StripeAudit/Index', [
            'estatisticas' => $estatisticas,
            'processos' => $processos->values(),
            'filtros' => [
                'search' => $busca,
            ],
        ]);
    }

    protected function montarProcessos(Collection $movimentacoes, Collection $pagamentos, Collection $eventos): Collection
    {
        $movimentacoesOrdenadas = $movimentacoes
            ->sortBy(fn (MovimentacaoAssinatura $movimentacao) => $movimentacao->created_at?->timestamp ?? 0)
            ->values();

        $pagamentosOrdenados = $pagamentos
            ->sortBy(fn (PagamentoStripe $pagamento) => ($pagamento->pago_em ?? $pagamento->created_at)?->timestamp ?? 0)
            ->values();

        $eventosOrdenados = $eventos
            ->sortBy(fn (EventoWebhookStripe $evento) => $evento->created_at?->timestamp ?? 0)
            ->values();

        $tiposAncora = [
            'checkout_iniciado',
            'troca_plano_solicitada',
            'cancelamento_solicitado',
            'checkout_sincronizacao_falhou',
            'troca_plano_falhou',
        ];

        $ancoras = $movimentacoesOrdenadas
            ->filter(fn (MovimentacaoAssinatura $movimentacao) => in_array($movimentacao->tipo_movimentacao, $tiposAncora, true))
            ->values();

        $idsMovimentacoesConsumidas = [];
        $idsPagamentosConsumidos = [];
        $idsEventosConsumidos = [];
        $processos = collect();

        foreach ($ancoras as $indice => $ancora) {
            $proximaAncoraMesmoUsuario = $ancoras
                ->slice($indice + 1)
                ->first(function (MovimentacaoAssinatura $proxima) use ($ancora) {
                    return $proxima->user_id === $ancora->user_id;
                });

            $inicio = $ancora->created_at ?? now();
            $fim = $proximaAncoraMesmoUsuario?->created_at ?? $inicio->copy()->addMinutes(30);

            $movimentacoesDoProcesso = $movimentacoesOrdenadas
                ->filter(function (MovimentacaoAssinatura $movimentacao) use ($ancora, $inicio, $fim, &$idsMovimentacoesConsumidas) {
                    if (in_array($movimentacao->id, $idsMovimentacoesConsumidas, true)) {
                        return false;
                    }

                    if ($movimentacao->user_id !== $ancora->user_id) {
                        return false;
                    }

                    if (!$this->dataEntre($movimentacao->created_at, $inicio->copy()->subSeconds(30), $fim)) {
                        return false;
                    }

                    if ($ancora->stripe_subscription_id && $movimentacao->stripe_subscription_id) {
                        return $ancora->stripe_subscription_id === $movimentacao->stripe_subscription_id;
                    }

                    return true;
                })
                ->values();

            $pagamentosDoProcesso = $pagamentosOrdenados
                ->filter(function (PagamentoStripe $pagamento) use ($ancora, $inicio, $fim, &$idsPagamentosConsumidos) {
                    if (in_array($pagamento->id, $idsPagamentosConsumidos, true)) {
                        return false;
                    }

                    if ($pagamento->user_id !== $ancora->user_id) {
                        return false;
                    }

                    $dataPagamento = $pagamento->pago_em ?? $pagamento->created_at;

                    if (!$this->dataEntre($dataPagamento, $inicio->copy()->subMinutes(5), $fim->copy()->addMinutes(10))) {
                        return false;
                    }

                    if ($ancora->stripe_subscription_id && $pagamento->stripe_subscription_id) {
                        return $ancora->stripe_subscription_id === $pagamento->stripe_subscription_id;
                    }

                    return true;
                })
                ->values();

            $eventosDoProcesso = $eventosOrdenados
                ->filter(function (EventoWebhookStripe $evento) use ($ancora, $inicio, $fim, &$idsEventosConsumidos) {
                    if (in_array($evento->id, $idsEventosConsumidos, true)) {
                        return false;
                    }

                    if ($evento->user_id !== $ancora->user_id) {
                        return false;
                    }

                    if (!$this->dataEntre($evento->created_at, $inicio->copy()->subMinutes(5), $fim->copy()->addMinutes(10))) {
                        return false;
                    }

                    if ($ancora->stripe_subscription_id && $evento->stripe_subscription_id) {
                        return $ancora->stripe_subscription_id === $evento->stripe_subscription_id;
                    }

                    return true;
                })
                ->values();

            foreach ($movimentacoesDoProcesso as $movimentacao) {
                $idsMovimentacoesConsumidas[] = $movimentacao->id;
            }

            foreach ($pagamentosDoProcesso as $pagamento) {
                $idsPagamentosConsumidos[] = $pagamento->id;
            }

            foreach ($eventosDoProcesso as $evento) {
                $idsEventosConsumidos[] = $evento->id;
            }

            $processos->push($this->formatarProcesso(
                'mov-' . $ancora->id,
                $ancora,
                $movimentacoesDoProcesso,
                $pagamentosDoProcesso,
                $eventosDoProcesso
            ));
        }

        $movimentacoesRestantes = $movimentacoesOrdenadas
            ->reject(fn (MovimentacaoAssinatura $movimentacao) => in_array($movimentacao->id, $idsMovimentacoesConsumidas, true))
            ->values();

        foreach ($movimentacoesRestantes as $movimentacao) {
            $processos->push($this->formatarProcesso(
                'mov-solto-' . $movimentacao->id,
                $movimentacao,
                collect([$movimentacao]),
                collect(),
                collect()
            ));
        }

        $pagamentosRestantes = $pagamentosOrdenados
            ->reject(fn (PagamentoStripe $pagamento) => in_array($pagamento->id, $idsPagamentosConsumidos, true))
            ->values();

        foreach ($pagamentosRestantes as $pagamento) {
            $processos->push($this->formatarProcessoAvulsoPagamento($pagamento));
        }

        $eventosRestantes = $eventosOrdenados
            ->reject(fn (EventoWebhookStripe $evento) => in_array($evento->id, $idsEventosConsumidos, true))
            ->values();

        foreach ($eventosRestantes as $evento) {
            $processos->push($this->formatarProcessoAvulsoEvento($evento));
        }

        return $processos
            ->sortByDesc(fn (array $processo) => strtotime((string) ($processo['ultima_atividade_em'] ?? $processo['criado_em'] ?? now()->toIso8601String())))
            ->values();
    }

    protected function formatarProcesso(
        string $id,
        MovimentacaoAssinatura $ancora,
        Collection $movimentacoes,
        Collection $pagamentos,
        Collection $eventos
    ): array {
        $statusResumo = $this->resolverStatusResumo($movimentacoes, $pagamentos, $eventos);
        $pagamentoPrincipal = $pagamentos->sortByDesc(fn (PagamentoStripe $pagamento) => ($pagamento->pago_em ?? $pagamento->created_at)?->timestamp ?? 0)->first();
        $ultimaAtividade = collect([
            $movimentacoes->max('created_at'),
            $pagamentos->map(fn (PagamentoStripe $pagamento) => $pagamento->pago_em ?? $pagamento->created_at)->filter()->sortByDesc(fn ($data) => $data?->timestamp ?? 0)->first(),
            $eventos->max('created_at'),
        ])->filter()->sortByDesc(fn ($data) => $data?->timestamp ?? 0)->first();

        return [
            'id' => $id,
            'titulo' => $this->tituloProcesso($ancora->tipo_movimentacao),
            'tipo_base' => $ancora->tipo_movimentacao,
            'status_resumo' => $statusResumo,
            'usuario' => $ancora->usuario ? [
                'id' => $ancora->usuario->id,
                'name' => $ancora->usuario->name,
                'email' => $ancora->usuario->email,
            ] : null,
            'plano_origem' => $ancora->planoOrigem?->name,
            'plano_destino' => $ancora->planoDestino?->name,
            'stripe_subscription_id' => $ancora->stripe_subscription_id,
            'stripe_price_id' => $ancora->stripe_price_id,
            'valor_formatado' => $pagamentoPrincipal
                ? $this->formatarValorCentavos($pagamentoPrincipal->valor_pago_centavos ?: $pagamentoPrincipal->valor_total_centavos, $pagamentoPrincipal->moeda ?: 'BRL')
                : null,
            'criado_em' => optional($ancora->created_at)->toIso8601String(),
            'ultima_atividade_em' => optional($ultimaAtividade)->toIso8601String(),
            'movimentacoes_total' => $movimentacoes->count(),
            'pagamentos_total' => $pagamentos->count(),
            'eventos_total' => $eventos->count(),
            'descricao' => $ancora->descricao,
            'andamentos' => [
                'movimentacoes' => $movimentacoes->map(fn (MovimentacaoAssinatura $movimentacao) => [
                    'id' => $movimentacao->id,
                    'tipo' => $movimentacao->tipo_movimentacao,
                    'status' => $movimentacao->status,
                    'descricao' => $movimentacao->descricao,
                    'origem' => $movimentacao->origem,
                    'created_at' => optional($movimentacao->created_at)->toIso8601String(),
                ])->values(),
                'pagamentos' => $pagamentos->map(fn (PagamentoStripe $pagamento) => [
                    'id' => $pagamento->id,
                    'status' => $pagamento->status,
                    'descricao' => $pagamento->descricao,
                    'motivo_cobranca' => $pagamento->motivo_cobranca,
                    'valor_formatado' => $this->formatarValorCentavos(
                        $pagamento->valor_pago_centavos ?: $pagamento->valor_total_centavos,
                        $pagamento->moeda ?: 'BRL'
                    ),
                    'stripe_invoice_id' => $pagamento->stripe_invoice_id,
                    'stripe_payment_intent_id' => $pagamento->stripe_payment_intent_id,
                    'created_at' => optional($pagamento->pago_em ?? $pagamento->created_at)->toIso8601String(),
                ])->values(),
                'eventos' => $eventos->map(fn (EventoWebhookStripe $evento) => [
                    'id' => $evento->id,
                    'tipo' => $evento->tipo_evento,
                    'status' => $evento->status_processamento,
                    'erro' => $evento->erro_processamento,
                    'stripe_event_id' => $evento->stripe_event_id,
                    'created_at' => optional($evento->created_at)->toIso8601String(),
                ])->values(),
            ],
        ];
    }

    protected function formatarProcessoAvulsoPagamento(PagamentoStripe $pagamento): array
    {
        return [
            'id' => 'pag-' . $pagamento->id,
            'titulo' => $pagamento->status === 'paid' ? 'Pagamento registrado' : 'Pagamento com pendencia',
            'tipo_base' => 'pagamento_avulso',
            'status_resumo' => $pagamento->status === 'paid' ? 'concluido' : 'processando',
            'usuario' => $pagamento->usuario ? [
                'id' => $pagamento->usuario->id,
                'name' => $pagamento->usuario->name,
                'email' => $pagamento->usuario->email,
            ] : null,
            'plano_origem' => null,
            'plano_destino' => $pagamento->plano?->name,
            'stripe_subscription_id' => $pagamento->stripe_subscription_id,
            'stripe_price_id' => $pagamento->stripe_price_id,
            'valor_formatado' => $this->formatarValorCentavos(
                $pagamento->valor_pago_centavos ?: $pagamento->valor_total_centavos,
                $pagamento->moeda ?: 'BRL'
            ),
            'criado_em' => optional($pagamento->created_at)->toIso8601String(),
            'ultima_atividade_em' => optional($pagamento->pago_em ?? $pagamento->created_at)->toIso8601String(),
            'movimentacoes_total' => 0,
            'pagamentos_total' => 1,
            'eventos_total' => 0,
            'descricao' => $pagamento->descricao,
            'andamentos' => [
                'movimentacoes' => [],
                'pagamentos' => [[
                    'id' => $pagamento->id,
                    'status' => $pagamento->status,
                    'descricao' => $pagamento->descricao,
                    'motivo_cobranca' => $pagamento->motivo_cobranca,
                    'valor_formatado' => $this->formatarValorCentavos(
                        $pagamento->valor_pago_centavos ?: $pagamento->valor_total_centavos,
                        $pagamento->moeda ?: 'BRL'
                    ),
                    'stripe_invoice_id' => $pagamento->stripe_invoice_id,
                    'stripe_payment_intent_id' => $pagamento->stripe_payment_intent_id,
                    'created_at' => optional($pagamento->pago_em ?? $pagamento->created_at)->toIso8601String(),
                ]],
                'eventos' => [],
            ],
        ];
    }

    protected function formatarProcessoAvulsoEvento(EventoWebhookStripe $evento): array
    {
        return [
            'id' => 'evt-' . $evento->id,
            'titulo' => 'Webhook Stripe recebido',
            'tipo_base' => 'evento_avulso',
            'status_resumo' => $evento->status_processamento === 'erro' ? 'erro' : 'concluido',
            'usuario' => $evento->usuario ? [
                'id' => $evento->usuario->id,
                'name' => $evento->usuario->name,
                'email' => $evento->usuario->email,
            ] : null,
            'plano_origem' => null,
            'plano_destino' => null,
            'stripe_subscription_id' => $evento->stripe_subscription_id,
            'stripe_price_id' => null,
            'valor_formatado' => null,
            'criado_em' => optional($evento->created_at)->toIso8601String(),
            'ultima_atividade_em' => optional($evento->processado_em ?? $evento->created_at)->toIso8601String(),
            'movimentacoes_total' => 0,
            'pagamentos_total' => 0,
            'eventos_total' => 1,
            'descricao' => $evento->tipo_evento,
            'andamentos' => [
                'movimentacoes' => [],
                'pagamentos' => [],
                'eventos' => [[
                    'id' => $evento->id,
                    'tipo' => $evento->tipo_evento,
                    'status' => $evento->status_processamento,
                    'erro' => $evento->erro_processamento,
                    'stripe_event_id' => $evento->stripe_event_id,
                    'created_at' => optional($evento->created_at)->toIso8601String(),
                ]],
            ],
        ];
    }

    protected function resolverStatusResumo(Collection $movimentacoes, Collection $pagamentos, Collection $eventos): string
    {
        if (
            $movimentacoes->contains(fn (MovimentacaoAssinatura $movimentacao) => $movimentacao->status === 'erro')
            || $eventos->contains(fn (EventoWebhookStripe $evento) => $evento->status_processamento === 'erro')
            || $pagamentos->contains(fn (PagamentoStripe $pagamento) => in_array($pagamento->status, ['void', 'uncollectible'], true))
        ) {
            return 'erro';
        }

        if (
            $pagamentos->contains(fn (PagamentoStripe $pagamento) => $pagamento->status === 'paid')
            || $movimentacoes->contains(fn (MovimentacaoAssinatura $movimentacao) => in_array($movimentacao->status, ['active', 'paid', 'processado'], true))
            || $eventos->contains(fn (EventoWebhookStripe $evento) => $evento->status_processamento === 'processado')
        ) {
            return 'concluido';
        }

        return 'processando';
    }

    protected function tituloProcesso(string $tipoMovimentacao): string
    {
        return match ($tipoMovimentacao) {
            'checkout_iniciado' => 'Novo checkout iniciado',
            'troca_plano_solicitada' => 'Troca de plano solicitada',
            'cancelamento_solicitado' => 'Cancelamento solicitado',
            'checkout_sincronizacao_falhou' => 'Falha apos checkout',
            'troca_plano_falhou' => 'Falha na troca de plano',
            default => str_replace('_', ' ', ucfirst($tipoMovimentacao)),
        };
    }

    protected function formatarValorCentavos(int $centavos, string $moeda): string
    {
        return number_format($centavos / 100, 2, ',', '.') . ' ' . strtoupper($moeda);
    }

    protected function dataEntre(?CarbonInterface $data, CarbonInterface $inicio, CarbonInterface $fim): bool
    {
        if (!$data) {
            return false;
        }

        return $data->greaterThanOrEqualTo($inicio) && $data->lessThanOrEqualTo($fim);
    }

    protected function aplicarBuscaEventos(Builder $consulta, string $busca): void
    {
        $consulta->where(function (Builder $query) use ($busca) {
            $query->where('stripe_event_id', 'like', "%{$busca}%")
                ->orWhere('stripe_customer_id', 'like', "%{$busca}%")
                ->orWhere('stripe_subscription_id', 'like', "%{$busca}%")
                ->orWhere('stripe_invoice_id', 'like', "%{$busca}%")
                ->orWhere('tipo_evento', 'like', "%{$busca}%")
                ->orWhereHas('usuario', function (Builder $usuarioQuery) use ($busca) {
                    $usuarioQuery->where('name', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");
                });
        });
    }

    protected function aplicarBuscaPagamentos(Builder $consulta, string $busca): void
    {
        $consulta->where(function (Builder $query) use ($busca) {
            $query->where('stripe_invoice_id', 'like', "%{$busca}%")
                ->orWhere('stripe_payment_intent_id', 'like', "%{$busca}%")
                ->orWhere('stripe_subscription_id', 'like', "%{$busca}%")
                ->orWhere('stripe_customer_id', 'like', "%{$busca}%")
                ->orWhere('descricao', 'like', "%{$busca}%")
                ->orWhereHas('usuario', function (Builder $usuarioQuery) use ($busca) {
                    $usuarioQuery->where('name', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");
                });
        });
    }

    protected function aplicarBuscaMovimentacoes(Builder $consulta, string $busca): void
    {
        $consulta->where(function (Builder $query) use ($busca) {
            $query->where('tipo_movimentacao', 'like', "%{$busca}%")
                ->orWhere('stripe_event_id', 'like', "%{$busca}%")
                ->orWhere('stripe_subscription_id', 'like', "%{$busca}%")
                ->orWhere('stripe_price_id', 'like', "%{$busca}%")
                ->orWhere('descricao', 'like', "%{$busca}%")
                ->orWhereHas('usuario', function (Builder $usuarioQuery) use ($busca) {
                    $usuarioQuery->where('name', 'like', "%{$busca}%")
                        ->orWhere('email', 'like', "%{$busca}%");
                });
        });
    }
}
