<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import NavegacaoGestao from '@/Components/Admin/NavegacaoGestao.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    estatisticas: {
        type: Object,
        required: true,
    },
    processos: {
        type: Array,
        required: true,
    },
    filtros: {
        type: Object,
        required: true,
    },
});

const busca = ref(props.filtros.search || '');
const processoExpandido = ref(null);

let temporizadorBusca = null;
watch(busca, () => {
    clearTimeout(temporizadorBusca);
    temporizadorBusca = setTimeout(() => {
        router.get(route('admin.stripe-audit.index'), {
            search: busca.value,
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 250);
});

const alternarProcesso = (id) => {
    processoExpandido.value = processoExpandido.value === id ? null : id;
};

const formatarDataHora = (valor) => {
    if (!valor) return '-';
    return new Date(valor).toLocaleString();
};

const classeStatus = (status) => {
    if (status === 'concluido') return 'bg-green-100 text-green-800';
    if (status === 'erro') return 'bg-red-100 text-red-800';
    return 'bg-yellow-100 text-yellow-800';
};

const classeStatusDetalhe = (status) => {
    if (['paid', 'active', 'processado'].includes(status)) return 'bg-green-100 text-green-800';
    if (['erro', 'void', 'uncollectible', 'canceled'].includes(status)) return 'bg-red-100 text-red-800';
    return 'bg-yellow-100 text-yellow-800';
};

const rotuloTipoProcesso = (tipo) => {
    const mapa = {
        checkout_iniciado: 'Novo checkout iniciado',
        troca_plano_solicitada: 'Troca de plano solicitada',
        cancelamento_solicitado: 'Cancelamento solicitado',
        checkout_sincronizado: 'Checkout sincronizado',
        checkout_sincronizacao_falhou: 'Falha apos checkout',
        troca_plano_confirmada_localmente: 'Troca confirmada localmente',
        troca_plano_falhou: 'Falha na troca de plano',
        assinatura_atualizada: 'Assinatura atualizada',
        assinatura_cancelada: 'Assinatura cancelada',
        pagamento_confirmado: 'Pagamento confirmado',
        pagamento_falhou: 'Pagamento falhou',
        pagamento_avulso: 'Lançamento financeiro avulso',
        evento_avulso: 'Evento Stripe avulso',
    };

    return mapa[tipo] || tipo;
};

const rotuloEventoWebhook = (tipo) => {
    const mapa = {
        'customer.subscription.updated': 'Assinatura atualizada na Stripe',
        'customer.subscription.created': 'Assinatura criada na Stripe',
        'customer.subscription.deleted': 'Assinatura cancelada na Stripe',
        'invoice.paid': 'Fatura paga',
        'invoice.payment_failed': 'Falha no pagamento da fatura',
        'invoice.created': 'Fatura criada',
        'invoiceitem.created': 'Item de fatura criado',
        'payment_intent.created': 'Tentativa de pagamento criada',
        'payment_intent.succeeded': 'Pagamento autorizado',
        'payment_intent.payment_failed': 'Tentativa de pagamento falhou',
        'setup_intent.canceled': 'Configuracao de pagamento cancelada',
    };

    return mapa[tipo] || tipo;
};

const eventoWebhookEhTecnico = (tipo) => {
    return [
        'invoiceitem.created',
        'invoice.created',
        'payment_intent.created',
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'setup_intent.canceled',
        'setup_intent.created',
        'charge.updated',
        'invoice.finalized',
    ].includes(tipo);
};

const movimentacoesPrincipais = (processo) => {
    return (processo.andamentos?.movimentacoes || []).filter((movimentacao) => {
        return [
            'checkout_iniciado',
            'troca_plano_solicitada',
            'cancelamento_solicitado',
            'checkout_sincronizado',
            'checkout_sincronizacao_falhou',
            'troca_plano_confirmada_localmente',
            'troca_plano_falhou',
            'assinatura_atualizada',
            'assinatura_cancelada',
            'pagamento_confirmado',
            'pagamento_falhou',
        ].includes(movimentacao.tipo);
    });
};

const eventosWebhookPrincipais = (processo) => {
    return (processo.andamentos?.eventos || []).filter((evento) => !eventoWebhookEhTecnico(evento.tipo));
};

const eventosWebhookTecnicos = (processo) => {
    return (processo.andamentos?.eventos || []).filter((evento) => eventoWebhookEhTecnico(evento.tipo));
};

const resumoNegocio = (processo) => {
    if (processo.status_resumo === 'erro') {
        return 'O processo encontrou falha em algum ponto da cobranca, sincronizacao ou webhook.';
    }

    if (processo.pagamentos_total > 0 && processo.status_resumo === 'concluido') {
        const resumoFinanceiro = processo.financeiro_resumo?.resumo;
        if (resumoFinanceiro) {
            return resumoFinanceiro;
        }

        return 'O processo possui registro financeiro e foi concluido localmente.';
    }

    if (processo.eventos_total > 0 && processo.movimentacoes_total === 0 && processo.pagamentos_total === 0) {
        return 'Apenas eventos da Stripe foram recebidos. Nao houve vinculo claro com um fluxo iniciado no sistema.';
    }

    return 'O processo foi registrado e processado sem erro, mas pode depender de detalhes tecnicos para interpretacao completa.';
};
</script>

<template>
    <Head title="Auditoria Stripe" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        Auditoria <span class="font-bold text-primary-600">Stripe</span>
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Uma linha por processo do usuario, com detalhamento dos andamentos ao abrir.
                    </p>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <NavegacaoGestao />

                <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Processos</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ estatisticas.total_processos }}</p>
                    </div>
                    <div class="rounded-lg border border-green-200 bg-green-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-green-700">Concluidos</p>
                        <p class="mt-2 text-2xl font-semibold text-green-900">{{ estatisticas.processos_concluidos }}</p>
                    </div>
                    <div class="rounded-lg border border-red-200 bg-red-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Com erro</p>
                        <p class="mt-2 text-2xl font-semibold text-red-900">{{ estatisticas.processos_com_erro }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pagamentos registrados</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ estatisticas.pagamentos_registrados }}</p>
                    </div>
                </div>

                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Buscar processo</label>
                    <input
                        v-model="busca"
                        type="text"
                        placeholder="Usuario, e-mail, subscription, invoice, evento..."
                        class="w-full rounded border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500"
                    >
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Processo</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Usuario</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Plano</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Financeiro</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Ultima atividade</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <template v-for="processo in processos" :key="processo.id">
                                <tr class="cursor-pointer hover:bg-gray-50" @click="alternarProcesso(processo.id)">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ processo.titulo }}</div>
                                        <div class="text-xs text-gray-400">{{ rotuloTipoProcesso(processo.tipo_base) }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-gray-900">{{ processo.usuario?.name || '-' }}</div>
                                        <div class="text-xs text-gray-400">{{ processo.usuario?.email || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ processo.plano_origem || '-' }} -> {{ processo.plano_destino || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-gray-900">
                                            {{ processo.financeiro_resumo?.valor_principal_formatado || '-' }}
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            {{ processo.financeiro_resumo?.rotulo_valor_principal || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ formatarDataHora(processo.ultima_atividade_em || processo.criado_em) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', classeStatus(processo.status_resumo)]">
                                            {{ processo.status_resumo }}
                                        </span>
                                    </td>
                                </tr>

                                <tr v-if="processoExpandido === processo.id" class="bg-gray-50/70">
                                    <td colspan="6" class="px-6 py-5">
                                        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                                <h3 class="mb-3 text-sm font-semibold text-gray-900">Resumo</h3>
                                                <dl class="space-y-2 text-sm">
                                                    <div>
                                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Leitura rapida</dt>
                                                        <dd class="text-gray-800">{{ resumoNegocio(processo) }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Descricao</dt>
                                                        <dd class="text-gray-800">{{ processo.descricao || '-' }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Subscription</dt>
                                                        <dd class="font-mono text-xs text-gray-700">{{ processo.stripe_subscription_id || '-' }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Price</dt>
                                                        <dd class="font-mono text-xs text-gray-700">{{ processo.stripe_price_id || '-' }}</dd>
                                                    </div>
                                                    <div>
                                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Contadores</dt>
                                                        <dd class="text-gray-700">
                                                            {{ processo.movimentacoes_total }} movimentacoes,
                                                            {{ processo.pagamentos_total }} pagamentos,
                                                            {{ processo.eventos_total }} eventos
                                                        </dd>
                                                    </div>
                                                    <div v-if="processo.financeiro_resumo">
                                                        <dt class="text-xs uppercase tracking-wide text-gray-500">Leitura financeira</dt>
                                                        <dd class="text-gray-700">
                                                            {{ processo.financeiro_resumo.titulo }}:
                                                            {{ processo.financeiro_resumo.valor_principal_formatado }}
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </div>

                                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                                <h3 class="mb-3 text-sm font-semibold text-gray-900">Andamentos principais</h3>
                                                <div v-if="movimentacoesPrincipais(processo).length === 0 && processo.andamentos.pagamentos.length === 0 && eventosWebhookPrincipais(processo).length === 0" class="text-sm text-gray-500">
                                                    Nenhum andamento principal identificado.
                                                </div>
                                                <div v-else class="space-y-3">
                                                    <div v-for="movimentacao in movimentacoesPrincipais(processo)" :key="`mov-${movimentacao.id}`" class="rounded border border-gray-200 p-3">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ rotuloTipoProcesso(movimentacao.tipo) }}</div>
                                                                <div class="text-xs text-gray-400">{{ formatarDataHora(movimentacao.created_at) }}</div>
                                                            </div>
                                                            <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', classeStatusDetalhe(movimentacao.status)]">
                                                                {{ movimentacao.status || '-' }}
                                                            </span>
                                                        </div>
                                                        <div class="mt-2 text-sm text-gray-600">{{ movimentacao.descricao || movimentacao.origem }}</div>
                                                    </div>

                                                    <div v-for="pagamento in processo.andamentos.pagamentos" :key="`pag-principal-${pagamento.id}`" class="rounded border border-green-200 bg-green-50/40 p-3">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ pagamento.titulo_financeiro }}</div>
                                                                <div class="text-xs text-gray-400">{{ formatarDataHora(pagamento.created_at) }}</div>
                                                            </div>
                                                            <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', classeStatusDetalhe(pagamento.status)]">
                                                                {{ pagamento.status || '-' }}
                                                            </span>
                                                        </div>
                                                        <div class="mt-2 text-sm font-medium text-gray-900">
                                                            {{ pagamento.rotulo_valor_principal }}: {{ pagamento.valor_principal_formatado }}
                                                        </div>
                                                        <div class="mt-1 text-sm text-gray-600">{{ pagamento.resumo_financeiro }}</div>
                                                        <dl class="mt-3 space-y-1 text-xs text-gray-600">
                                                            <div class="flex justify-between gap-4">
                                                                <dt>Valor final da invoice</dt>
                                                                <dd class="font-medium text-gray-800">{{ pagamento.valor_fatura_formatado }}</dd>
                                                            </div>
                                                            <div class="flex justify-between gap-4">
                                                                <dt>Valor cobrado agora</dt>
                                                                <dd class="font-medium text-gray-800">{{ pagamento.valor_cobrado_agora_formatado }}</dd>
                                                            </div>
                                                            <div v-if="pagamento.valor_credito_formatado" class="flex justify-between gap-4">
                                                                <dt>Credito identificado</dt>
                                                                <dd class="font-medium text-gray-800">{{ pagamento.valor_credito_formatado }}</dd>
                                                            </div>
                                                            <div v-if="pagamento.valor_abatimento_formatado" class="flex justify-between gap-4">
                                                                <dt>Abatimento identificado</dt>
                                                                <dd class="font-medium text-gray-800">{{ pagamento.valor_abatimento_formatado }}</dd>
                                                            </div>
                                                            <div v-if="pagamento.motivo_cobranca_legivel" class="flex justify-between gap-4">
                                                                <dt>Motivo Stripe</dt>
                                                                <dd class="font-medium text-gray-800">{{ pagamento.motivo_cobranca_legivel }}</dd>
                                                            </div>
                                                        </dl>
                                                        <div class="mt-2 text-xs text-gray-500">{{ pagamento.descricao || pagamento.motivo_cobranca || '-' }}</div>
                                                    </div>

                                                    <div v-for="evento in eventosWebhookPrincipais(processo)" :key="`evt-principal-${evento.id}`" class="rounded border border-blue-200 bg-blue-50/40 p-3">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div>
                                                                <div class="font-medium text-gray-900">{{ rotuloEventoWebhook(evento.tipo) }}</div>
                                                                <div class="text-xs text-gray-400">{{ formatarDataHora(evento.created_at) }}</div>
                                                            </div>
                                                            <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', classeStatusDetalhe(evento.status)]">
                                                                {{ evento.status || '-' }}
                                                            </span>
                                                        </div>
                                                        <div class="mt-2 font-mono text-xs text-gray-500">{{ evento.stripe_event_id || '-' }}</div>
                                                        <div v-if="evento.erro" class="mt-2 text-sm text-red-600">{{ evento.erro }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                                <h3 class="mb-3 text-sm font-semibold text-gray-900">Detalhes tecnicos da Stripe</h3>

                                                <div class="space-y-4">
                                                    <div>
                                                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Eventos tecnicos</div>
                                                        <div v-if="eventosWebhookTecnicos(processo).length === 0" class="text-sm text-gray-500">Nenhum evento tecnico relevante.</div>
                                                        <div v-else class="space-y-3">
                                                            <div v-for="evento in eventosWebhookTecnicos(processo)" :key="`evt-${evento.id}`" class="rounded border border-gray-200 p-3">
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <div class="font-medium text-gray-900">{{ rotuloEventoWebhook(evento.tipo) }}</div>
                                                                    <span :class="['inline-flex rounded-full px-2 py-1 text-xs font-semibold', classeStatusDetalhe(evento.status)]">
                                                                        {{ evento.status || '-' }}
                                                                    </span>
                                                                </div>
                                                                <div class="mt-1 text-xs text-gray-400">{{ formatarDataHora(evento.created_at) }}</div>
                                                                <div class="mt-2 font-mono text-xs text-gray-500">{{ evento.stripe_event_id || '-' }}</div>
                                                                <div v-if="evento.erro" class="mt-2 text-sm text-red-600">{{ evento.erro }}</div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">Identificadores</div>
                                                        <div class="rounded border border-gray-200 bg-gray-50 p-3 font-mono text-xs text-gray-600">
                                                            sub: {{ processo.stripe_subscription_id || '-' }}<br>
                                                            price: {{ processo.stripe_price_id || '-' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="processos.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-sm italic text-gray-500">
                                    Nenhum processo encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
