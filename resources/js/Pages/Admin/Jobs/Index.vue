<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    jobs: Object,
    filters: Object,
});

const statusFilter = ref(props.filters.status || 'todos');
const sort = ref({
    column: props.filters.sort_by || 'created_at',
    order: props.filters.sort_order || 'desc'
});

const updateQuery = () => {
    router.get(route('admin.jobs.index'), { 
        status: statusFilter.value,
        sort_by: sort.value.column,
        sort_order: sort.value.order
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(statusFilter, (value) => {
    updateQuery();
});

const sortBy = (column) => {
    if (sort.value.column === column) {
        sort.value.order = sort.value.order === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value.column = column;
        sort.value.order = 'asc';
    }
    updateQuery();
};

const cancelarProcesso = (id) => {
    if(confirm('Tem certeza que deseja forçar o encerramento deste rastreio? Isso o marcará como Failed e as URLs descobertas não serão salvas ativamente a menos que o job saiba lidar com SIGTERM, caso contrário ele falhará imediatamente.')) {
        router.delete(route('admin.jobs.cancel', id));
    }
};

const formatData = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString() + ' ' + new Date(iso).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});
};
</script>

<template>
    <Head title="Monitoramento Crawler" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex items-center justify-center gap-3">
                    <svg class="w-8 h-8 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        Monitor de Crawler (Jobs)
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
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Tabela de Fila:</span>
                            <select v-model="statusFilter" class="border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm focus:ring-primary-500 dark:text-white flex-1 sm:flex-none">
                                <option value="todos">Todos (Histórico Global)</option>
                                <option value="queued">Na Fila (Aguardando PID)</option>
                                <option value="running">Em Processamento (Motor Girando)</option>
                                <option value="completed">Sucesso (Spider Salvo)</option>
                                <option value="failed">Falhados (Exceptions)</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-100 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th @click="sortBy('id')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                                        <div class="flex items-center gap-1">
                                            Sessão #ID
                                            <svg v-if="sort.column === 'id'" :class="sort.order === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4">Alvo URL</th>
                                    <th @click="sortBy('status')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                                        <div class="flex items-center gap-1">
                                            Status Motor
                                            <svg v-if="sort.column === 'status'" :class="sort.order === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th @click="sortBy('pages_processed')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                                        <div class="flex items-center gap-1">
                                            Progresso / Páginas
                                            <svg v-if="sort.column === 'pages_processed'" :class="sort.order === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th @click="sortBy('created_at')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                                        <div class="flex items-center gap-1">
                                            Iniciado (Timestamp)
                                            <svg v-if="sort.column === 'created_at'" :class="sort.order === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-right">Controles</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="job in jobs.data" :key="job.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold font-mono text-gray-900 dark:text-white tracking-widest">{{ String(job.id).padStart(5, '0') }}</div>
                                        <div class="text-[10px] text-gray-400 mt-1 uppercase">Dono: {{ job.projeto?.user?.name || 'Sistema' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a :href="job.projeto?.url || '#'" target="_blank" class="font-medium text-primary-600 hover:underline max-w-[200px] truncate block">
                                            {{ job.projeto?.url || 'Domínio Destruído' }}
                                        </a>
                                        <div class="text-xs text-gray-500 mt-0.5">Proj: {{ job.projeto?.name || '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'px-2.5 py-1 text-xs font-bold rounded-sm border uppercase shadow-sm',
                                            job.status === 'queued' ? 'bg-gray-50 text-gray-700 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600' :
                                            (job.status === 'running' ? 'bg-accent-50 text-accent-700 border-accent-300 animate-pulse dark:bg-accent-900/30 dark:text-accent-400 dark:border-accent-700' :
                                            (job.status === 'completed' ? 'bg-success-50 text-success-700 border-success-300 dark:bg-success-900/30 dark:text-success-400 dark:border-success-700' :
                                            'bg-danger-50 text-danger-700 border-danger-300 dark:bg-danger-900/30 dark:text-danger-400 dark:border-danger-700'))
                                        ]">
                                            <span v-if="job.status === 'running'" class="inline-block w-2 h-2 mr-1 rounded-full bg-accent-500 animate-bounce"></span>
                                            {{ job.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="mb-1 text-xs font-semibold" :class="job.status === 'failed' ? 'text-danger-500' : 'text-gray-700 dark:text-gray-300'">
                                            {{ job.pages_processed || 0 }} páginas indexadas
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ formatData(job.created_at) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button v-if="['running', 'queued'].includes(job.status)" 
                                                @click="cancelarProcesso(job.id)"
                                                class="text-danger-600 hover:text-danger-800 font-medium text-xs bg-danger-50 hover:bg-danger-100 px-2.5 py-1 rounded dark:bg-danger-900/40 dark:text-danger-400 border border-danger-200 dark:border-danger-800"
                                            >
                                                Forçar Parada
                                            </button>
                                            <Link :href="route('admin.jobs.show', job.id)" class="text-gray-600 hover:text-primary-700 font-medium text-xs bg-gray-100 hover:bg-gray-200 px-2.5 py-1 rounded dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600 transition">
                                                Ver Logs Internos
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="jobs.data.length === 0">
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                        Nenhuma spider encontrada ou o grid de pesquisa retornou zero pipelines ativas.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação Simples -->
                    <div v-if="jobs.links && jobs.data.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex flex-col sm:flex-row items-center justify-between">
                        <p class="text-sm text-gray-700 dark:text-gray-400 mb-4 sm:mb-0">
                            Mostrando de <span class="font-medium">{{ jobs.from }}</span> a <span class="font-medium">{{ jobs.to }}</span> de <span class="font-medium">{{ jobs.total }}</span> resultados
                        </p>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <template v-for="(link, k) in jobs.links" :key="k">
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
