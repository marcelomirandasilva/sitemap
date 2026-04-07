<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { trans as t } from 'laravel-vue-i18n';

const props = defineProps({
    tickets: { type: Array, default: () => [] },
    projetos: { type: Array, default: () => [] },
});

const abaAtiva = ref('novo');

// --- Formulário de Novo Ticket ---
const form = useForm({
    titulo: '',
    projeto_id: '',
    mensagem: '',
    anexos: [],
});

const arquivosSelecionados = ref([]);

const aoSelecionarArquivos = (event) => {
    form.anexos = Array.from(event.target.files);
    arquivosSelecionados.value = Array.from(event.target.files).map(f => f.name);
};

const enviarTicket = () => {
    form.post(route('support.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            arquivosSelecionados.value = [];
            abaAtiva.value = 'historico';
        },
    });
};

// --- Utilitários ---
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
</script>

<template>
    <Head :title="t('support.title')" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ t('support.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">

                    <!-- Abas -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex" aria-label="Abas">
                            <button
                                @click="abaAtiva = 'novo'"
                                :class="[
                                    abaAtiva === 'novo'
                                        ? 'border-primary-500 text-primary-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200'
                                ]"
                            >
                                🎫 {{ t('support.tab_new') }}
                            </button>
                            <button
                                @click="abaAtiva = 'historico'"
                                :class="[
                                    abaAtiva === 'historico'
                                        ? 'border-primary-500 text-primary-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200'
                                ]"
                            >
                                🕐 {{ t('support.tab_history') }}
                            </button>
                        </nav>
                    </div>

                    <!-- ABA: Novo Ticket -->
                    <div v-show="abaAtiva === 'novo'" class="p-6">
                        <header class="mb-6 bg-primary-50 p-3 rounded-md border-l-4 border-primary-400">
                            <h3 class="text-sm font-bold text-primary-900 uppercase tracking-wide">
                                🎫 {{ t('support.form_title') }}
                            </h3>
                        </header>

                        <form @submit.prevent="enviarTicket" class="space-y-5">

                            <!-- Assunto -->
                            <div>
                                <label class="block text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-1 rounded mb-1">
                                    {{ t('support.field_subject') }}
                                    <span class="text-danger-500 font-normal ml-1">({{ t('support.required') }})</span>
                                </label>
                                <input
                                    v-model="form.titulo"
                                    type="text"
                                    class="mt-1 block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                    :placeholder="t('support.field_subject_placeholder')"
                                />
                                <p v-if="form.errors.titulo" class="text-sm text-danger-600 mt-1">{{ form.errors.titulo }}</p>
                            </div>

                            <!-- Projeto Relacionado -->
                            <div>
                                <label class="block text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-1 rounded mb-1">
                                    {{ t('support.field_project') }}
                                </label>
                                <select
                                    v-model="form.projeto_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                >
                                    <option value="">{{ t('support.field_project_none') }}</option>
                                    <option v-for="p in projetos" :key="p.id" :value="p.id">
                                        {{ p.nome }}
                                    </option>
                                </select>
                            </div>

                            <!-- Mensagem -->
                            <div>
                                <label class="block text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-1 rounded mb-1">
                                    {{ t('support.field_message') }}
                                    <span class="text-danger-500 font-normal ml-1">({{ t('support.required') }})</span>
                                </label>
                                <textarea
                                    v-model="form.mensagem"
                                    rows="8"
                                    class="mt-1 block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                    :placeholder="t('support.field_message_placeholder')"
                                ></textarea>
                                <p v-if="form.errors.mensagem" class="text-sm text-danger-600 mt-1">{{ form.errors.mensagem }}</p>
                            </div>

                            <!-- Anexos -->
                            <div>
                                <label class="block text-xs font-semibold text-primary-700 bg-primary-50 px-2 py-1 rounded mb-1">
                                    {{ t('support.field_attachments') }}
                                </label>
                                <p class="text-xs text-gray-400 mb-2">
                                    {{ t('support.field_attachments_hint') }}
                                </p>
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

                            <!-- Botão Enviar -->
                            <div>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white font-bold py-3 px-8 rounded uppercase tracking-wider text-sm transition-colors"
                                >
                                    {{ form.processing ? t('support.submitting') : t('support.submit_button') }}
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- ABA: Histórico -->
                    <div v-show="abaAtiva === 'historico'" class="p-6">
                        <header class="mb-6 bg-primary-50 p-3 rounded-md border-l-4 border-primary-400">
                            <h3 class="text-sm font-bold text-primary-900 uppercase tracking-wide">
                                🕐 {{ t('support.history_title') }}
                            </h3>
                        </header>

                        <div v-if="tickets.length === 0" class="text-center py-12 text-gray-500">
                            <p class="text-4xl mb-4">🎫</p>
                            <p>{{ t('support.no_tickets') }}</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ t('support.col_subject') }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ t('support.col_project') }}</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-600">{{ t('support.col_status') }}</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-600">{{ t('support.col_date') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="ticket in tickets"
                                        :key="ticket.id"
                                        class="hover:bg-gray-50 cursor-pointer transition-colors"
                                        @click="$inertia.visit(route('support.show', ticket.id))"
                                    >
                                        <td class="px-4 py-3 font-mono text-gray-400">#{{ ticket.id }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-800">
                                            {{ ticket.titulo }}
                                            <span v-if="ticket.respostas > 0" class="ml-2 text-xs bg-primary-100 text-primary-700 px-1.5 py-0.5 rounded-full">
                                                💬 {{ ticket.respostas }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ ticket.projeto || '-' }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span :class="['inline-block px-2 py-1 rounded-full text-xs font-semibold', corStatus(ticket.status)]">
                                                {{ t('support.status_' + ticket.status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-400">{{ formatarData(ticket.criado_em) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
