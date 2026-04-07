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
    order: props.filters.sort_order || 'desc',
});

const updateQuery = () => {
    router.get(route('admin.jobs.index'), {
        status: statusFilter.value,
        sort_by: sort.value.column,
        sort_order: sort.value.order,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(statusFilter, () => {
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
    if (confirm('Tem certeza que deseja cancelar este job remoto?')) {
        router.delete(route('admin.jobs.cancel', id));
    }
};

const formatData = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString() + ' ' + new Date(iso).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const statusBadge = (status) => {
    if (status === 'completed') return 'bg-green-50 text-green-700 border-green-200';
    if (status === 'running') return 'bg-sky-50 text-sky-700 border-sky-200';
    if (status === 'queued') return 'bg-slate-50 text-slate-700 border-slate-200';
    if (status === 'cancelled') return 'bg-amber-50 text-amber-700 border-amber-200';
    return 'bg-red-50 text-red-700 border-red-200';
};
</script>

<template>
    <Head title="Monitoramento Crawler" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex items-center justify-center gap-3">
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        Monitor de Jobs
                    </h1>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex space-x-4 mb-8 border-b border-gray-200 dark:border-gray-700 pb-2 overflow-x-auto">
                    <Link :href="route('admin.dashboard')" :class="route().current('admin.dashboard') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Painel Executivo</Link>
                    <Link :href="route('admin.users.index')" :class="route().current('admin.users.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Usuários</Link>
                    <Link :href="route('admin.plans.index')" :class="route().current('admin.plans.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Planos</Link>
                    <Link :href="route('admin.tickets.index')" :class="route().current('admin.tickets.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Suporte</Link>
                    <Link :href="route('admin.jobs.index')" :class="route().current('admin.jobs.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Jobs</Link>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Status:</span>
                            <select v-model="statusFilter" class="border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm focus:ring-primary-500 dark:text-white flex-1 sm:flex-none">
                                <option value="todos">Todos</option>
                                <option value="queued">Queued</option>
                                <option value="running">Running</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-100 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th @click="sortBy('id')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">ID</th>
                                    <th class="px-6 py-4">Projeto</th>
                                    <th @click="sortBy('status')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">Status</th>
                                    <th @click="sortBy('progress')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">Progresso</th>
                                    <th @click="sortBy('pages_count')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">URLs</th>
                                    <th @click="sortBy('started_at')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">Início</th>
                                    <th @click="sortBy('completed_at')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">Fim</th>
                                    <th class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="job in jobs.data" :key="job.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold font-mono text-gray-900 dark:text-white">#{{ String(job.id).padStart(5, '0') }}</div>
                                        <div class="text-[11px] text-gray-400">{{ job.external_job_id }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ job.projeto?.name || '-' }}</div>
                                        <div class="text-xs text-gray-500 max-w-[260px] truncate">{{ job.projeto?.url || '-' }}</div>
                                        <div class="text-[11px] text-gray-400">Dono: {{ job.projeto?.user?.name || 'Sistema' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="['px-2.5 py-1 text-xs font-bold rounded border uppercase', statusBadge(job.status)]">
                                            {{ job.status }}
                                        </span>
                                        <div v-if="job.message" class="text-xs text-gray-500 mt-2 max-w-[260px] truncate">{{ job.message }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900 dark:text-white">{{ Math.floor(job.progress || 0) }}%</div>
                                        <div class="w-32 h-2 bg-gray-200 rounded-full mt-2 overflow-hidden">
                                            <div class="h-full bg-primary-500" :style="{ width: Math.max(4, Math.floor(job.progress || 0)) + '%' }"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs">
                                        <div><span class="font-semibold">{{ job.pages_count || 0 }}</span> no sitemap</div>
                                        <div>Found: {{ job.urls_found || 0 }}</div>
                                        <div>Crawled: {{ job.urls_crawled || 0 }}</div>
                                        <div>Excluded: {{ job.urls_excluded || 0 }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ formatData(job.started_at || job.created_at) }}</td>
                                    <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ formatData(job.completed_at) }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <button
                                                v-if="['running', 'queued'].includes(job.status)"
                                                @click="cancelarProcesso(job.id)"
                                                class="text-danger-600 hover:text-danger-800 font-medium text-xs bg-danger-50 hover:bg-danger-100 px-2.5 py-1 rounded border border-danger-200"
                                            >
                                                Cancelar
                                            </button>
                                            <Link :href="route('admin.jobs.show', job.id)" class="text-gray-600 hover:text-primary-700 font-medium text-xs bg-gray-100 hover:bg-gray-200 px-2.5 py-1 rounded border border-gray-200 transition">
                                                Ver detalhes
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="jobs.data.length === 0">
                                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                                        Nenhum job encontrado para os filtros atuais.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

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
