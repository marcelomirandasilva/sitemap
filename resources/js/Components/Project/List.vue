<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';
import StatusRastreador from '@/Components/Crawler/Status.vue';

const props = defineProps({
    projetos: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const sortKey = ref('updated_at');
const sortOrder = ref('desc');

const columns = [
    { key: 'project', label: 'Projeto', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'coverage', label: 'Cobertura', sortable: true },
    { key: 'updated_at', label: 'Atualizado', sortable: true },
    { key: 'actions', label: 'Acoes', sortable: false },
];

const formataData = (data) => {
    if (!data) return '-';

    return new Date(data).toLocaleDateString(t('locale') === 'pt' ? 'pt-BR' : 'en-US', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const normalizarDominio = (url) => {
    if (!url) {
        return '-';
    }

    return url.replace(/^https?:\/\//, '').replace(/\/$/, '');
};

const obterStatusJob = (projeto) => projeto.ultimo_job?.status || 'waiting';

const obterTotalPaginas = (projeto) => Number(
    projeto.ultimo_job?.pages_count
    ?? projeto.ultimo_job?.urls_found
    ?? projeto.ultimo_job?.urls_crawled
    ?? 0
);

const obterTextoCobertura = (projeto) => {
    const ultimoJob = projeto.ultimo_job;

    if (!ultimoJob) {
        return 'Sem execucao';
    }

    if (['queued', 'running'].includes(ultimoJob.status)) {
        return `Processando (${Math.round(ultimoJob.progress || 0)}%)`;
    }

    return `${obterTotalPaginas(projeto)} paginas`;
};

const obterResumoProjeto = (projeto) => {
    const partes = [];

    if (projeto.name) {
        partes.push(projeto.name);
    }

    if (projeto.ultimo_job?.external_job_id) {
        partes.push(`Job ${projeto.ultimo_job.external_job_id.slice(0, 8)}`);
    }

    return partes.join(' • ');
};

const getSortableValue = (obj, key) => {
    if (key === 'project') {
        return `${normalizarDominio(obj.url)} ${obj.name || ''}`.trim();
    }

    if (key === 'status') {
        return obterStatusJob(obj);
    }

    if (key === 'coverage') {
        return obterTotalPaginas(obj);
    }

    if (key === 'updated_at') {
        return obj.ultimo_job?.updated_at || obj.created_at || '';
    }

    return '';
};

const filteredProjects = computed(() => {
    let result = [...props.projetos];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((projeto) =>
            projeto.url.toLowerCase().includes(query)
            || (projeto.name && projeto.name.toLowerCase().includes(query))
            || obterStatusJob(projeto).toLowerCase().includes(query)
            || String(projeto.id).includes(query)
        );
    }

    if (sortKey.value) {
        result.sort((a, b) => {
            const valA = getSortableValue(a, sortKey.value);
            const valB = getSortableValue(b, sortKey.value);

            if (valA < valB) {
                return sortOrder.value === 'asc' ? -1 : 1;
            }

            if (valA > valB) {
                return sortOrder.value === 'asc' ? 1 : -1;
            }

            return 0;
        });
    }

    return result;
});

const totalItems = computed(() => filteredProjects.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalItems.value / pageSize.value)));
const startItem = computed(() => (totalItems.value === 0 ? 0 : ((currentPage.value - 1) * pageSize.value) + 1));
const endItem = computed(() => Math.min(currentPage.value * pageSize.value, totalItems.value));

const paginatedProjects = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    return filteredProjects.value.slice(start, start + pageSize.value);
});

const onSearch = () => {
    currentPage.value = 1;
};

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
        return;
    }

    sortKey.value = key;
    sortOrder.value = key === 'updated_at' ? 'desc' : 'asc';
};

const confirmarExclusao = async (projeto) => {
    const confirmacao = await Swal.fire({
        title: t('project.delete_confirm_title'),
        text: t('project.delete_confirm_text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: t('project.delete_confirm_btn'),
        cancelButtonText: t('project.delete_cancel_btn'),
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    router.delete(route('projects.destroy', { projeto: projeto.id }));
};
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col items-start justify-between gap-4 border-b border-gray-200 bg-gray-50 p-4 md:flex-row md:items-center">
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span>{{ $t('table.show') }}</span>
                <select v-model="pageSize" class="rounded border-gray-300 py-1 text-sm shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                </select>
                <span>{{ $t('table.entries') }}</span>
            </div>

            <div class="relative w-full md:w-72">
                <input
                    v-model="searchQuery"
                    type="text"
                    :placeholder="$t('table.search_placeholder') || 'Buscar projeto...'"
                    class="w-full rounded border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-primary-400 focus:ring-1 focus:ring-primary-400"
                    @input="onSearch"
                >
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-3 transition-colors"
                            :class="col.sortable ? 'cursor-pointer select-none hover:bg-gray-100' : ''"
                            @click="col.sortable && toggleSort(col.key)"
                        >
                            <div class="flex items-center gap-1" :class="{ 'text-primary-600': sortKey === col.key }">
                                <span>{{ $t(col.label) }}</span>
                                <span v-if="sortKey === col.key" class="text-[10px]">
                                    {{ sortOrder === 'asc' ? '▲' : '▼' }}
                                </span>
                                <span v-else-if="col.sortable" class="text-[10px] text-gray-300">▲▼</span>
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    <tr v-for="projeto in paginatedProjects" :key="projeto.id" class="align-top transition-colors hover:bg-gray-50">
                        <td class="w-[34%] px-4 py-4">
                            <div class="space-y-2">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <Link :href="route('projects.show', projeto.id)" class="block truncate text-lg font-semibold text-accent-600 hover:underline">
                                            {{ normalizarDominio(projeto.url) }}
                                        </Link>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ obterResumoProjeto(projeto) || 'Projeto sem titulo' }}
                                        </p>
                                    </div>

                                    <span class="shrink-0 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                        #{{ projeto.id }}
                                    </span>
                                </div>

                                <a :href="projeto.url" target="_blank" rel="noopener noreferrer" class="block truncate text-xs text-gray-400 hover:text-accent-600">
                                    {{ projeto.url }}
                                </a>
                            </div>
                        </td>

                        <td class="w-[16%] px-4 py-4">
                            <div class="space-y-2">
                                <StatusRastreador :projeto="projeto" :ultima-tarefa="projeto.ultimo_job" :simple="true" />
                                <p class="text-xs capitalize text-gray-500">
                                    {{ obterStatusJob(projeto) }}
                                </p>
                            </div>
                        </td>

                        <td class="w-[18%] px-4 py-4">
                            <div class="space-y-1 text-sm">
                                <p class="font-semibold text-gray-700">
                                    {{ obterTextoCobertura(projeto) }}
                                </p>
                                <p v-if="projeto.ultimo_job" class="text-xs text-gray-500">
                                    Found: {{ projeto.ultimo_job.urls_found ?? 0 }}
                                </p>
                                <p v-if="projeto.ultimo_job" class="text-xs text-gray-500">
                                    Crawled: {{ projeto.ultimo_job.urls_crawled ?? 0 }}
                                </p>
                                <p v-if="projeto.ultimo_job" class="text-xs text-gray-500">
                                    Excluded: {{ projeto.ultimo_job.urls_excluded ?? 0 }}
                                </p>
                            </div>
                        </td>

                        <td class="w-[14%] px-4 py-4">
                            <div class="space-y-1 text-sm">
                                <p class="font-medium text-gray-700">
                                    {{ projeto.ultimo_job?.updated_at ? formataData(projeto.ultimo_job.updated_at) : '-' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    Criado em {{ formataData(projeto.created_at) }}
                                </p>
                            </div>
                        </td>

                        <td class="w-[18%] bg-gray-50/50 px-4 py-4">
                            <div class="flex flex-col gap-2">
                                <Link
                                    :href="route('projects.show', projeto.id)"
                                    class="inline-flex items-center justify-center rounded border border-gray-300 bg-white px-3 py-2 text-[11px] font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                >
                                    {{ $t('project.view_action') || 'Ver' }}
                                </Link>

                                <StatusRastreador :projeto="projeto" :ultima-tarefa="projeto.ultimo_job" :action-only="true" />

                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded border border-danger-200 bg-white px-3 py-2 text-[11px] font-semibold uppercase tracking-widest text-danger-600 shadow-sm transition hover:bg-danger-50 focus:outline-none focus:ring-2 focus:ring-danger-500 focus:ring-offset-2"
                                    @click="confirmarExclusao(projeto)"
                                >
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="paginatedProjects.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                            {{ $t('table.no_records') || 'Nenhum projeto encontrado' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="totalItems > 0" class="flex flex-col items-center justify-between gap-4 border-t border-gray-200 bg-gray-50 px-6 py-4 text-xs text-gray-500 md:flex-row">
            <div>
                {{ $t('table.showing') }}
                <span class="font-bold">{{ startItem }}</span>
                {{ $t('table.to') }}
                <span class="font-bold">{{ endItem }}</span>
                {{ $t('table.of') }}
                <span class="font-bold">{{ totalItems }}</span>
                {{ $t('table.entries') }}
            </div>

            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="rounded border border-gray-300 bg-white px-3 py-1 shadow-sm transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="currentPage === 1"
                    @click="currentPage > 1 && currentPage--"
                >
                    {{ $t('pagination.previous') }}
                </button>

                <span class="px-2 text-gray-400">Page {{ currentPage }} / {{ totalPages }}</span>

                <button
                    type="button"
                    class="rounded border border-gray-300 bg-white px-3 py-1 shadow-sm transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="currentPage === totalPages"
                    @click="currentPage < totalPages && currentPage++"
                >
                    {{ $t('pagination.next') }}
                </button>
            </div>
        </div>
    </div>
</template>
