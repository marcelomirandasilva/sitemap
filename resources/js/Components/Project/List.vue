<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import StatusRastreador from '@/Components/Crawler/Status.vue';

const props = defineProps({
    projetos: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['click-status']);

// --- Estado Interno do DataTable ---
const searchQuery = ref('');
const currentPage = ref(1);
const pageSize = ref(10);
const sortKey = ref('updated_at'); // Coluna padrão
const sortOrder = ref('desc'); // 'asc' or 'desc'

// --- Colunas Configuráveis ---
const columns = [
    { key: 'status', label: 'table.status', sortable: true },
    { key: 'url', label: 'table.domain', sortable: true },
    { key: 'name', label: 'table.title', sortable: true },
    { key: 'updated_at', label: 'table.updated', sortable: true },
];

// --- Métodos Auxiliares ---
const formataData = (data) => {
    if (!data) return '';
    return new Date(data).toLocaleDateString(t('locale') === 'pt' ? 'pt-BR' : 'en-US', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};



// --- Lógica de Ordenação e Filtro ---
const filteredProjects = computed(() => {
    let result = [...props.projetos];

    // 1. Busca Local
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(p => 
            p.url.toLowerCase().includes(query) || 
            (p.name && p.name.toLowerCase().includes(query)) ||
            (p.latest_job?.status && p.latest_job.status.toLowerCase().includes(query))
        );
    }

    // 2. Ordenação
    if (sortKey.value) {
        result.sort((a, b) => {
            let valA = getSortableValue(a, sortKey.value);
            let valB = getSortableValue(b, sortKey.value);

            if (valA < valB) return sortOrder.value === 'asc' ? -1 : 1;
            if (valA > valB) return sortOrder.value === 'asc' ? 1 : -1;
            return 0;
        });
    }

    return result;
});

const getSortableValue = (obj, key) => {
    if (key === 'status') return obj.latest_job?.status || '';
    if (key === 'updated_at') return obj.latest_job?.updated_at || obj.created_at || '';
    if (key === 'url') return obj.url || '';
    if (key === 'name') return obj.name || '';
    return '';
};

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = 'asc';
    }
};

// --- Lógica de Paginação ---
const totalItems = computed(() => filteredProjects.value.length);
const totalPages = computed(() => Math.ceil(totalItems.value / pageSize.value));
const startItem = computed(() => (currentPage.value - 1) * pageSize.value + 1);
const endItem = computed(() => Math.min(currentPage.value * pageSize.value, totalItems.value));

const paginatedProjects = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    const end = start + pageSize.value;
    return filteredProjects.value.slice(start, end);
});

// Resetar página ao buscar
const onSearch = () => {
    currentPage.value = 1;
};
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <!-- DataTable Header (Controls) -->
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
            
            <!-- Page Size -->
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <span>{{ $t('table.show') }}</span>
                <select v-model="pageSize" class="border-gray-300 focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 rounded shadow-sm text-sm py-1">
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                </select>
                <span>{{ $t('table.entries') }}</span>
            </div>

            <!-- Search -->
            <div class="relative w-full md:w-64">
                <input 
                    v-model="searchQuery"
                    @input="onSearch"
                    type="text" 
                    :placeholder="$t('table.search_placeholder') || 'Search...'" 
                    class="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-blue-400 focus:border-blue-400"
                >
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                        <th 
                            v-for="col in columns" 
                            :key="col.key"
                            @click="col.sortable && toggleSort(col.key)"
                            :class="['px-6 py-3 cursor-pointer hover:bg-gray-100 transition-colors select-none', {'text-blue-600': sortKey === col.key}]"
                        >
                            <div class="flex items-center gap-1">
                                {{ $t(col.label) }}
                                <span v-if="sortKey === col.key" class="text-[10px]">
                                    {{ sortOrder === 'asc' ? '▲' : '▼' }}
                                </span>
                                <span v-else class="text-gray-300 text-[10px]">▲▼</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="projeto in paginatedProjects" :key="projeto.id" class="hover:bg-gray-50 transition-colors">
                        
                        <!-- Status -->
                        <td class="px-6 py-4 align-top w-48">
                            <div class="flex flex-col gap-2 items-start">
                                <StatusRastreador :projeto="projeto" :ultima-tarefa="projeto.latest_job" :simple="true" />
                            </div>
                        </td>

                        <!-- Domain -->
                        <td class="px-6 py-4 align-top font-medium">
                            <Link :href="route('projects.show', projeto.id)" class="text-[#c0392b] hover:underline block truncate">
                                {{ projeto.url.replace(/^https?:\/\//, '').replace(/\/$/, '') }}
                            </Link>
                        </td>

                        <!-- Title -->
                        <td class="px-6 py-4 align-top text-gray-500 text-sm">
                             {{ projeto.name || '-' }}
                        </td>

                        <!-- Updated / Crawler Status -->
                        <td class="px-6 py-4 align-top w-1/3">
                            <div class="flex flex-col gap-1">
                                <div class="text-xs text-gray-500 mb-1" v-if="projeto.latest_job">
                                   {{ projeto.latest_job.status === 'completed' ? formataData(projeto.latest_job.updated_at) : '' }}
                                   <span v-if="projeto.latest_job.status === 'completed'">, {{ projeto.latest_job.pages_count }} {{ $t('project.pages_count') }}</span>
                                   <span v-else>
                                       Sitemap processing...
                                   </span>
                                </div>
                                
                                <StatusRastreador :projeto="projeto" :ultima-tarefa="projeto.latest_job" />
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Empty State -->
                    <tr v-if="paginatedProjects.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 text-sm">
                            {{ $t('table.no_records') || 'No matching records found' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- DataTable Footer (Pagination) -->
        <div v-if="totalItems > 0" class="px-6 py-4 border-t border-gray-200 bg-gray-50 text-xs text-gray-500 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                {{ $t('table.showing') }} <span class="font-bold">{{ startItem }}</span> {{ $t('table.to') }} <span class="font-bold">{{ endItem }}</span> {{ $t('table.of') }} <span class="font-bold">{{ totalItems }}</span> {{ $t('table.entries') }}
            </div>
            
            <div class="flex items-center gap-1">
                <button 
                    @click="currentPage > 1 && currentPage--"
                    :disabled="currentPage === 1"
                    class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition"
                >
                    {{ $t('pagination.previous') }}
                </button>
                
                <span class="px-2 text-gray-400">Page {{ currentPage }} / {{ totalPages }}</span>

                <button 
                    @click="currentPage < totalPages && currentPage++"
                    :disabled="currentPage === totalPages"
                    class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition"
                >
                    {{ $t('pagination.next') }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.simulate_arrow::before {
    content: "︽"; 
    font-size: 10px;
    margin-right: 2px;
}
</style>