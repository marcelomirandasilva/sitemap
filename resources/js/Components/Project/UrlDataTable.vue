<script setup>
import { ref, watch, onMounted } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import axios from 'axios';

const props = defineProps({
    projectId: {
        type: Number,
        required: true
    }
});

const urls = ref([]);
const total = ref(0);
const loading = ref(false);
const page = ref(1);
const perPage = ref(25);
const searchQuery = ref('');

const columns = [
    { key: 'url', label: 'project.location' }, // Translation keys
    { key: 'lastMod', label: 'project.last_mod', align: 'right' },
    { key: 'priority', label: 'project.priority', align: 'right' },
    { key: 'changeFreq', label: 'project.change_freq', align: 'right' }
];

const fetchUrls = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('crawler.urls', props.projectId), {
            params: {
                page: page.value,
                per_page: perPage.value,
                q: searchQuery.value
            }
        });
        
        urls.value = response.data.data;
        total.value = response.data.total;
    } catch (error) {
        console.error("Failed to fetch URLs", error);
    } finally {
        loading.value = false;
    }
};

// Debounce search
let timeout;
const onSearch = () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        page.value = 1;
        fetchUrls();
    }, 400);
};

// Helper para formatar frequência
const formatFreq = (freq) => {
    if (!freq) return '-';
    // Remove o prefixo "ChangeFrequency." se existir
    const clean = freq.replace('ChangeFrequency.', '');
    // Usa a chave de tradução "freq.lowercase"
    return t('freq.' + clean.toLowerCase());
};

// Helper para formatar data
const formatDate = (dateStr) => {
    if (!dateStr || dateStr === 'NULO') return '-';
    
    try {
        // Tenta substituir espaço por T para garantir ISO 8601 (Safari/Firefox safe)
        // ex: "2026-01-29 17:28:02+00:00" -> "2026-01-29T17:28:02+00:00"
        let safeDate = dateStr.replace(' ', 'T');
        
        const date = new Date(safeDate);
        
        // Se der inválido, tenta parsing manual ou retorna string original
        if (isNaN(date.getTime())) {
             return dateStr;
        }

        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateStr;
    }
};

// Watchers
watch([page, perPage], fetchUrls);

onMounted(() => {
    fetchUrls();
});
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <!-- Toolbar -->
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col md:flex-row justify-between items-center gap-4">
             <div class="flex items-center gap-2 text-sm text-gray-600">
                <span>{{ $t('table.show') }}</span>
                <select v-model="perPage" class="border-gray-300 focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 rounded shadow-sm text-sm py-1">
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                    <option :value="100">100</option>
                </select>
                <span>{{ $t('table.entries') }}</span>
            </div>

            <div class="relative w-full md:w-64">
                <input 
                    v-model="searchQuery"
                    @input="onSearch"
                    type="text" 
                    :placeholder="$t('table.search_placeholder')" 
                    class="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-blue-400 focus:border-blue-400"
                >
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto relative min-h-[200px]">
            <div v-if="loading" class="absolute inset-0 bg-white/80 z-10 flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <table class="w-full text-left text-sm text-gray-500">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase font-bold text-gray-600">
                    <tr>
                         <th v-for="col in columns" :key="col.key" :class="['px-6 py-3', col.align === 'right' ? 'text-right' : 'text-left']">
                            {{ $t(col.label) }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="(item, i) in urls" :key="i" class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-3 font-medium text-[#c0392b] break-all max-w-md">
                            {{ item.url }}
                            <a :href="item.url" target="_blank" class="ml-2 text-gray-300 hover:text-blue-500">↗</a>
                        </td>
                        <td class="px-6 py-3 text-right text-gray-400">{{ formatDate(item.lastMod) }}</td>
                        <td class="px-6 py-3 text-right text-gray-600 font-mono">{{ item.priority || '-' }}</td>
                        <td class="px-6 py-3 text-right text-gray-600">{{ formatFreq(item.changeFreq) }}</td>
                    </tr>
                     <tr v-if="!loading && urls.length === 0">
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            {{ $t('table.no_records') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="total > 0" class="px-6 py-4 border-t border-gray-200 bg-gray-50 text-xs text-gray-500 flex justify-between items-center">
            <div>
                 {{ $t('table.showing') }} <span class="font-bold">{{ (page - 1) * perPage + 1 }}</span> {{ $t('table.to') }} <span class="font-bold">{{ Math.min(page * perPage, total) }}</span> {{ $t('table.of') }} <span class="font-bold">{{ total }}</span> {{ $t('table.entries') }}
            </div>
            <div class="flex gap-1">
                <button 
                    @click="page > 1 && page--" 
                    :disabled="page === 1"
                    class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 shadow-sm"
                >
                    {{ $t('pagination.previous') }}
                </button>
                <span class="px-2 py-1">{{ page }}</span>
                <button 
                    @click="(page * perPage < total) && page++" 
                    :disabled="page * perPage >= total"
                    class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 shadow-sm"
                >
                    {{ $t('pagination.next') }}
                </button>
            </div>
        </div>
    </div>
</template>
