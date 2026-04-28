<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import NavegacaoGestao from '@/Components/Admin/NavegacaoGestao.vue';

const props = defineProps({
    jobs: Object,
    filters: Object,
});

const statusFilter = ref(props.filters.status || 'todos');
const sort = ref({
    column: props.filters.sort_by || 'created_at',
    order: props.filters.sort_order || 'desc',
});
const searchQuery = ref('');

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

const jobsFiltrados = computed(() => {
    const termo = searchQuery.value.trim().toLowerCase();

    if (!termo) {
        return props.jobs.data;
    }

    return props.jobs.data.filter((job) => {
        const campos = [
            String(job.id ?? ''),
            job.external_job_id ?? '',
            job.status ?? '',
            job.message ?? '',
            job.projeto?.name ?? '',
            job.projeto?.url ?? '',
            job.projeto?.user?.name ?? '',
        ];

        return campos.some((valor) => valor.toLowerCase().includes(termo));
    });
});
</script>

<template>
    <Head title="Monitoramento Crawler" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="mx-auto flex max-w-7xl items-center justify-center gap-3 px-4 text-center sm:px-6 lg:px-8">
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        Monitor de Jobs
                    </h1>
                </div>
            </div>

            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <NavegacaoGestao />

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Status:</span>
                            <select v-model="statusFilter" class="rounded border-gray-300 bg-white text-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="todos">Todos</option>
                                <option value="queued">Queued</option>
                                <option value="running">Running</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>

                        <div class="relative w-full lg:w-72">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Buscar na página atual..."
                                class="w-full rounded border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-primary-400 focus:ring-1 focus:ring-primary-400"
                            >
                            <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="border-b border-gray-200 bg-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <tr>
                                    <th @click="sortBy('status')" class="px-6 py-4 cursor-pointer transition hover:bg-gray-200 dark:hover:bg-gray-800">Status</th>
                                    <th class="px-6 py-4">Processo</th>
                                    <th @click="sortBy('progress')" class="px-6 py-4 cursor-pointer transition hover:bg-gray-200 dark:hover:bg-gray-800">Progresso</th>
                                    <th @click="sortBy('pages_count')" class="px-6 py-4 cursor-pointer transition hover:bg-gray-200 dark:hover:bg-gray-800">URLs</th>
                                    <th @click="sortBy('started_at')" class="px-6 py-4 cursor-pointer transition hover:bg-gray-200 dark:hover:bg-gray-800">Início</th>
                                    <th @click="sortBy('completed_at')" class="px-6 py-4 cursor-pointer transition hover:bg-gray-200 dark:hover:bg-gray-800">Fim</th>
                                    <th class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="job in jobsFiltrados" :key="job.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="w-[240px] px-6 py-4 align-top">
                                        <span :class="['rounded border px-2.5 py-1 text-xs font-bold uppercase', statusBadge(job.status)]">
                                            {{ job.status }}
                                        </span>
                                        <div v-if="job.message" class="mt-3 max-w-[220px] text-xs leading-5 text-gray-500">
                                            {{ job.message }}
                                        </div>
                                    </td>

                                    <td class="min-w-0 px-6 py-4 align-top">
                                        <div class="font-mono text-base font-bold text-gray-900 dark:text-white">
                                            #{{ String(job.id).padStart(5, '0') }}
                                        </div>
                                        <div class="mt-1 break-all text-xs leading-5 text-gray-400">
                                            {{ job.external_job_id }}
                                        </div>
                                        <div class="mt-3 font-medium text-gray-900 dark:text-white">
                                            {{ job.projeto?.name || '-' }}
                                        </div>
                                        <div class="mt-1 break-all text-sm leading-5 text-gray-500">
                                            {{ job.projeto?.url || '-' }}
                                        </div>
                                        <div class="mt-1 text-xs leading-5 text-gray-400">
                                            Dono: {{ job.projeto?.user?.name || 'Sistema' }}
                                        </div>
                                    </td>

                                    <td class="w-[180px] px-6 py-4 align-top">
                                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ Math.floor(job.progress || 0) }}%
                                        </div>
                                        <div class="mt-3 h-2 w-full max-w-[130px] overflow-hidden rounded-full bg-gray-200">
                                            <div class="h-full bg-primary-500" :style="{ width: Math.max(4, Math.floor(job.progress || 0)) + '%' }"></div>
                                        </div>
                                    </td>

                                    <td class="w-[190px] px-6 py-4 align-top text-sm leading-6 text-gray-600 dark:text-gray-300">
                                        <div><span class="font-semibold text-gray-900 dark:text-white">{{ job.pages_count || 0 }}</span> no sitemap</div>
                                        <div>Found: {{ job.urls_found || 0 }}</div>
                                        <div>Crawled: {{ job.urls_crawled || 0 }}</div>
                                        <div>Excluded: {{ job.urls_excluded || 0 }}</div>
                                    </td>

                                    <td class="w-[170px] px-6 py-4 align-top font-mono text-xs leading-5 text-gray-500">
                                        {{ formatData(job.started_at || job.created_at) }}
                                    </td>

                                    <td class="w-[170px] px-6 py-4 align-top font-mono text-xs leading-5 text-gray-500">
                                        {{ formatData(job.completed_at) }}
                                    </td>

                                    <td class="w-[150px] px-6 py-4 align-top text-right">
                                        <div class="flex flex-col items-end gap-2">
                                            <button
                                                v-if="['running', 'queued'].includes(job.status)"
                                                @click="cancelarProcesso(job.id)"
                                                class="rounded border border-danger-200 bg-danger-50 px-3 py-1.5 text-xs font-medium text-danger-600 hover:bg-danger-100 hover:text-danger-800"
                                            >
                                                Cancelar
                                            </button>
                                            <Link
                                                :href="route('admin.jobs.show', job.id)"
                                                class="rounded border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-200 hover:text-primary-700"
                                            >
                                                Ver detalhes
                                            </Link>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="jobsFiltrados.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                        Nenhum job encontrado para os filtros atuais.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="jobs.links && jobs.data.length > 0" class="flex flex-col items-center justify-between gap-4 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50 sm:flex-row">
                        <p class="text-sm text-gray-700 dark:text-gray-400">
                            Mostrando de <span class="font-medium">{{ jobs.from }}</span> a <span class="font-medium">{{ jobs.to }}</span> de <span class="font-medium">{{ jobs.total }}</span> resultados
                        </p>
                        <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <template v-for="(link, k) in jobs.links" :key="k">
                                <component
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    v-html="link.label"
                                    class="relative inline-flex items-center border px-4 py-2 text-sm font-medium"
                                    :class="[
                                        link.active ? 'z-10 border-primary-500 bg-primary-50 text-primary-600 dark:bg-primary-900/20' : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700',
                                        !link.url ? 'cursor-not-allowed opacity-50' : ''
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
