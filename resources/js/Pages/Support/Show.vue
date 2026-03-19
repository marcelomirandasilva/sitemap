<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { trans as t } from 'laravel-vue-i18n';

const props = defineProps({
    ticket: { type: Object, required: true },
});

// Controle do formulário de resposta expansível
const mostrarFormResposta = ref(false);
const arquivosSelecionados = ref([]);

const formResposta = useForm({
    mensagem: '',
    anexos: [],
});

const aoSelecionarArquivos = (event) => {
    formResposta.anexos = Array.from(event.target.files);
    arquivosSelecionados.value = Array.from(event.target.files).map(f => f.name);
};

const enviarResposta = () => {
    formResposta.post(route('support.reply', props.ticket.id), {
        forceFormData: true,
        onSuccess: () => {
            formResposta.reset();
            arquivosSelecionados.value = [];
            mostrarFormResposta.value = false;
        },
    });
};

const corStatus = (status) => {
    const cores = {
        aberto: 'bg-blue-100 text-blue-800',
        em_analise: 'bg-yellow-100 text-yellow-800',
        em_atendimento: 'bg-orange-100 text-orange-800',
        respondido: 'bg-green-100 text-green-800',
        aguardando_usuario: 'bg-purple-100 text-purple-800',
        fechado: 'bg-gray-100 text-gray-600',
    };
    return cores[status] || 'bg-gray-100 text-gray-600';
};

const formatarData = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const ticketFechado = props.ticket.status === 'fechado';
</script>

<template>
    <Head :title="'#' + ticket.id + ' — ' + ticket.titulo" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('support.index')" class="text-primary-600 hover:underline text-sm">
                    ← {{ t('support.title') }}
                </Link>
                <span class="text-gray-300">/</span>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">
                    #{{ ticket.id }} — {{ ticket.titulo }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Cabeçalho do Ticket -->
                <div class="bg-white shadow sm:rounded-lg p-6">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">{{ ticket.titulo }}</h3>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ t('support.col_project') }}: <strong>{{ ticket.projeto || t('support.field_project_none') }}</strong>
                                &nbsp;·&nbsp;
                                {{ t('support.col_date') }}: {{ formatarData(ticket.criado_em) }}
                            </p>
                        </div>
                        <span :class="['inline-block px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap', corStatus(ticket.status)]">
                            {{ t('support.status_' + ticket.status) }}
                        </span>
                    </div>

                    <!-- Mensagem original -->
                    <div class="bg-gray-50 rounded-md p-4 text-sm text-gray-700 whitespace-pre-wrap border border-gray-100">
                        {{ ticket.mensagem }}
                    </div>

                    <!-- Anexos originais -->
                    <div v-if="ticket.anexos?.length" class="mt-4">
                        <p class="text-xs font-semibold text-gray-500 mb-2 uppercase tracking-wide">📎 {{ t('support.field_attachments') }}</p>
                        <div class="flex flex-wrap gap-3">
                            <a
                                v-for="anexo in ticket.anexos"
                                :key="anexo.id"
                                :href="anexo.url"
                                target="_blank"
                                class="block border border-gray-200 rounded overflow-hidden hover:opacity-90 transition"
                            >
                                <img :src="anexo.url" :alt="anexo.nome_original" class="h-24 w-auto object-cover" />
                                <p class="text-xs text-center text-primary-600 px-2 py-1 truncate max-w-[150px]">{{ anexo.nome_original }}</p>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Timeline de Respostas -->
                <div v-if="ticket.respostas?.length" class="space-y-4">
                    <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wide px-1">
                        💬 {{ t('support.responses') }}
                    </h4>

                    <div
                        v-for="resposta in ticket.respostas"
                        :key="resposta.id"
                        :class="[
                            'bg-white shadow sm:rounded-lg p-4 border-l-4',
                            resposta.is_admin ? 'border-primary-400' : 'border-gray-200'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">{{ resposta.autor }}</span>
                            <div class="flex items-center gap-2">
                                <span v-if="resposta.is_admin" class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full font-semibold">
                                    {{ t('support.admin') }}
                                </span>
                                <span class="text-xs text-gray-400">{{ formatarData(resposta.criado_em) }}</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ resposta.mensagem }}</p>
                    </div>
                </div>

                <!-- Painel de Resposta do Usuário (apenas se ticket não estiver fechado) -->
                <div v-if="!ticketFechado" class="bg-white shadow sm:rounded-lg overflow-hidden">

                    <!-- Cabeçalho do painel -->
                    <div class="bg-gray-100 px-4 py-3 flex items-center gap-3 border-b border-gray-200">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <span class="font-semibold text-sm text-gray-700">{{ t('support.your_reply') }}</span>
                    </div>

                    <div class="p-5">
                        <!-- Mensagem padrão -->
                        <p class="text-sm text-gray-600 mb-3">{{ t('support.reply_info') }}</p>

                        <!-- Link para expandir formulário -->
                        <button
                            v-if="!mostrarFormResposta"
                            @click="mostrarFormResposta = true"
                            class="text-sm font-bold text-red-600 hover:underline"
                        >
                            {{ t('support.reply_click_here') }}
                        </button>

                        <!-- Formulário expansível -->
                        <div v-if="mostrarFormResposta" class="mt-4 space-y-4">

                            <div>
                                <label class="block text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-1 rounded mb-1">
                                    {{ t('support.field_message') }}
                                    <span class="text-red-500 font-normal ml-1">({{ t('support.required') }})</span>
                                </label>
                                <textarea
                                    v-model="formResposta.mensagem"
                                    rows="6"
                                    class="mt-1 block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm"
                                    :placeholder="t('support.field_message_placeholder')"
                                ></textarea>
                                <p v-if="formResposta.errors.mensagem" class="text-sm text-red-600 mt-1">{{ formResposta.errors.mensagem }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-1 rounded mb-1">
                                    {{ t('support.field_attachments') }}
                                </label>
                                <p class="text-xs text-gray-400 mb-2">{{ t('support.field_attachments_hint') }}</p>
                                <input
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png"
                                    @change="aoSelecionarArquivos"
                                    class="block text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                                />
                                <ul v-if="arquivosSelecionados.length" class="mt-2 text-xs text-gray-500 space-y-1">
                                    <li v-for="(nome, i) in arquivosSelecionados" :key="i">📎 {{ nome }}</li>
                                </ul>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    @click="enviarResposta"
                                    :disabled="formResposta.processing"
                                    class="bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white font-bold py-2 px-6 rounded uppercase tracking-wider text-sm transition-colors"
                                >
                                    {{ formResposta.processing ? t('support.submitting') : t('support.send_reply') }}
                                </button>
                                <button
                                    @click="mostrarFormResposta = false"
                                    class="text-sm text-gray-500 hover:text-gray-700"
                                >
                                    {{ t('support.cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Botão Fechar Ticket -->
                    <div class="px-5 pb-5">
                        <Link
                            :href="route('support.fechar', ticket.id)"
                            method="patch"
                            as="button"
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded uppercase tracking-wider text-sm transition-colors"
                            onclick="return confirm('Tem certeza que deseja encerrar este ticket?')"
                        >
                            {{ t('support.close_ticket') }}
                        </Link>
                    </div>
                </div>

                <!-- Ticket encerrado -->
                <div v-else class="bg-gray-50 shadow sm:rounded-lg p-5 text-center text-gray-400 border border-gray-200">
                    <p class="text-2xl mb-2">🔒</p>
                    <p class="text-sm">{{ t('support.ticket_closed_msg') }}</p>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
