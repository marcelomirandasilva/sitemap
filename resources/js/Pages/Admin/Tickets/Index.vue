<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    tickets: Object,
    filters: Object,
});

const statusFilter = ref(props.filters.status || 'todos');

watch(statusFilter, (value) => {
    router.get(route('admin.tickets.index'), { status: value }, {
        preserveState: true,
        replace: true,
    });
});

const formatData = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString() + ' ' + new Date(iso).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
};
</script>

<template>
    <Head title="Suporte (Tickets)" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex items-center justify-center gap-3">
                    <svg class="w-8 h-8 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        Gerenciar Tickets de Suporte
                    </h1>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Navigation Tabs Administration -->
                <div class="flex space-x-4 mb-8 border-b border-gray-200 dark:border-gray-700 pb-2 overflow-x-auto">
                    <Link :href="route('admin.dashboard')" :class="route().current('admin.dashboard') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Painel Executivo</Link>
                    <Link :href="route('admin.users.index')" :class="route().current('admin.users.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Usuários</Link>
                    <Link :href="route('admin.plans.index')" :class="route().current('admin.plans.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Planos</Link>
                    <Link :href="route('admin.tickets.index')" :class="route().current('admin.tickets.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Suporte (Tickets)</Link>
                    <Link :href="route('admin.jobs.index')" :class="route().current('admin.jobs.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Motor Crawler</Link>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Filtrar por Status:</span>
                            <select v-model="statusFilter" class="border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm focus:ring-primary-500 dark:text-white flex-1 sm:flex-none">
                                <option value="todos">Todos os Tickets</option>
                                <option value="aberto">Abertos</option>
                                <option value="respondido">Respondidos</option>
                                <option value="fechado">Fechados</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-100 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-4">Protocolo / Título</th>
                                    <th class="px-6 py-4">Cliente</th>
                                    <th class="px-6 py-4">Projeto Relacionado</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Atualizado Em</th>
                                    <th class="px-6 py-4 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">#TKT-{{ String(ticket.id).padStart(4, '0') }}</div>
                                        <div class="text-xs text-gray-500 max-w-[12rem] truncate">{{ ticket.assunto }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium">{{ ticket.usuario?.name || 'Deletado' }}</div>
                                        <div class="text-xs text-gray-400">{{ ticket.usuario?.email || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="ticket.projeto_id" class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-600 dark:text-gray-300">{{ ticket.projeto?.name || 'Projeto Deletado' }}</span>
                                        <span v-else class="text-xs text-gray-400">Geral</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'px-2.5 py-1 text-xs font-medium rounded-full',
                                            ticket.status === 'aberto' ? 'bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-300' :
                                            (ticket.status === 'respondido' ? 'bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300' :
                                            'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-300')
                                        ]">
                                            {{ ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono">{{ formatData(ticket.updated_at) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <Link :href="route('admin.tickets.show', ticket.id)" class="text-primary-600 hover:text-primary-900 font-medium whitespace-nowrap">
                                            Analisar Chat
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="tickets.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        Nenhum ticket encontrado nesta caixa.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação Simples -->
                    <div v-if="tickets.links && tickets.data.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex flex-col sm:flex-row items-center justify-between">
                        <p class="text-sm text-gray-700 dark:text-gray-400 mb-4 sm:mb-0">
                            Mostrando de <span class="font-medium">{{ tickets.from }}</span> a <span class="font-medium">{{ tickets.to }}</span> de <span class="font-medium">{{ tickets.total }}</span> resultados
                        </p>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <template v-for="(link, k) in tickets.links" :key="k">
                                <component 
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    v-html="link.label"
                                    class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                    :class="[
                                        link.active ? 'z-10 bg-primary-50 border-primary-500 text-primary-600 dark:bg-primary-900/20' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
