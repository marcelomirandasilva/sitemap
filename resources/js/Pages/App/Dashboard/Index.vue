<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import CartaoProjeto from '@/Components/Project/CartaoProjeto.vue';
import LinhaTabelaProjeto from '@/Components/Project/LinhaTabelaProjeto.vue';
import ModalProgressoRastreador from '@/Components/Crawler/ModalProgressoRastreador.vue';

const props = defineProps({
    projetos: {
        type: Array,
        default: () => []
    }
});

// Formulário para adicionar o site
const formulario = useForm({
    url: '',
});

const mostrarModalAdicionar = ref(false);
const mostrarModalProgresso = ref(false);
const projetoSelecionado = ref(null);

const termoBusca = ref('');
const modoVisualizacao = ref('grid'); // 'grid' | 'list'
const filtroAtivo = ref('todos'); // 'todos' | 'gratis' | 'progresso'

const salvarProjeto = () => {
    formulario.post(route('projects.store'), {
        onSuccess: () => {
             formulario.reset();
             mostrarModalAdicionar.value = false;
             
             // Abre o modal de progresso automaticamente para o novo projeto (primeiro da lista)
             // Assumindo que o back retorna latest(). Precisa garantir que props.projetos atualizou? 
             // O Inertia atualiza props automaticamente. Vamos pegar o topo.
             if (props.projetos.length > 0) {
                 projetoSelecionado.value = props.projetos[0];
                 mostrarModalProgresso.value = true;
             }
        },
    });
};

const abrirProgresso = (projeto) => {
    projetoSelecionado.value = projeto;
    mostrarModalProgresso.value = true;
};

// Computados para contagem dos filtros
const contagens = computed(() => {
    return {
        todos: props.projetos.length,
        gratis: props.projetos.filter(p => !p.has_advanced_features || p.max_pages <= 500).length,
        progresso: props.projetos.filter(p => p.latest_job && ['running', 'queued'].includes(p.latest_job.status)).length
    };
});

const projetosFiltrados = computed(() => {
    let resultado = props.projetos;

    // 1. Filtro de Status/Tipo
    if (filtroAtivo.value === 'gratis') {
        resultado = resultado.filter(p => !p.has_advanced_features || p.max_pages <= 500);
    } else if (filtroAtivo.value === 'progresso') {
        resultado = resultado.filter(p => p.latest_job && ['running', 'queued'].includes(p.latest_job.status));
    }

    // 2. Filtro de Busca
    if (termoBusca.value) {
        const termo = termoBusca.value.toLowerCase();
        resultado = resultado.filter(p => 
            p.url.toLowerCase().includes(termo) || 
            (p.name && p.name.toLowerCase().includes(termo))
        );
    }

    return resultado;
});
</script>

<template>
    <Head :title="$t('dashboard.control_panel')" />

    <AppLayout>
        <template #hero>
            <!-- Hero "Pro" -->
            <div class="bg-blue-50 border-b border-blue-100 py-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-3xl font-light text-gray-700 flex items-center justify-center gap-3">
                        <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        {{ $t('dashboard.control_panel') }}
                    </h1>
                </div>
            </div>

            <!-- Toolbar & Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Action Bar -->
                <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6 gap-4">
                    <button 
                        @click="mostrarModalAdicionar = !mostrarModalAdicionar"
                        class="bg-[#007da0] hover:bg-[#006480] text-white font-bold py-2.5 px-6 rounded text-sm uppercase tracking-wide transition shadow-sm flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        {{ $t('dashboard.add_another') }}
                    </button>

                    <div class="relative w-full md:w-96">
                        <input 
                            v-model="termoBusca"
                            type="text" 
                            :placeholder="$t('dashboard.search_placeholder')" 
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded text-gray-600 focus:ring-1 focus:ring-blue-400 focus:border-blue-400 bg-gray-50 shadow-inner"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                <!-- Filters Bar -->
                 <div v-if="projetos.length > 0" class="flex flex-col md:flex-row justify-between items-center mb-6 px-2 gap-4">
                    <!-- Tabs -->
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <button 
                            @click="filtroAtivo = 'todos'"
                            :class="['px-3 py-1 rounded transition border', filtroAtivo === 'todos' ? 'bg-white border-gray-300 shadow-sm font-bold text-gray-800' : 'border-transparent hover:bg-gray-100']"
                        >
                            {{ $t('dashboard.show_all') }} <span class="bg-gray-100 border border-gray-200 px-1.5 rounded-md text-xs ml-1">{{ contagens.todos }}</span>
                        </button>
                        <button 
                            @click="filtroAtivo = 'gratis'"
                            :class="['px-3 py-1 rounded transition border', filtroAtivo === 'gratis' ? 'bg-white border-gray-300 shadow-sm font-bold text-gray-800' : 'border-transparent hover:bg-gray-100']"
                        >
                            {{ $t('dashboard.filters.free_sites') }} <span class="bg-gray-100 border border-gray-200 px-1.5 rounded-md text-xs ml-1">{{ contagens.gratis }}</span>
                        </button>
                        <button 
                            @click="filtroAtivo = 'progresso'"
                            :class="['px-3 py-1 rounded transition border', filtroAtivo === 'progresso' ? 'bg-white border-gray-300 shadow-sm font-bold text-gray-800' : 'border-transparent hover:bg-gray-100']"
                        >
                            {{ $t('dashboard.filters.in_progress') }} <span class="bg-gray-100 border border-gray-200 px-1.5 rounded-md text-xs ml-1">{{ contagens.progresso }}</span>
                        </button>
                    </div>

                    <!-- View Toggle -->
                    <button 
                        @click="modoVisualizacao = modoVisualizacao === 'grid' ? 'list' : 'grid'"
                        class="text-xs font-bold uppercase text-[#c0392b] flex items-center gap-1 hover:underline"
                    >
                        <svg v-if="modoVisualizacao === 'list'" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                        {{ modoVisualizacao === 'grid' ? $t('dashboard.switch_list') : $t('dashboard.switch_grid') }}
                    </button>
                </div>

                <!-- Add Form Toggle (Simple) -->
                <div v-if="mostrarModalAdicionar" class="mb-8 p-6 bg-gray-50 border border-blue-100 rounded-lg animate-fade-in-down">
                    <h3 class="text-lg font-light text-gray-600 mb-4 text-center">{{ $t('dashboard.add_new_title') }}</h3>
                    <form @submit.prevent="salvarProjeto" class="max-w-md mx-auto flex gap-2">
                        <input 
                            v-model="formulario.url"
                            type="url" 
                            required
                            placeholder="https://example.com"
                            class="flex-1 border border-gray-300 shadow-inner px-4 py-3 text-gray-600 focus:ring-1 focus:ring-blue-400 focus:border-blue-400 rounded-md"
                        >
                        <button 
                            :disabled="formulario.processing"
                            type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 rounded shadow-sm disabled:opacity-50"
                        >
                            {{ $t('dashboard.add_submit') }}
                        </button>
                    </form>
                </div>

                <!-- Project Grid / List -->
                <div v-if="projetosFiltrados.length > 0">
                    
                    <!-- Grid View -->
                    <div v-if="modoVisualizacao === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <CartaoProjeto 
                            v-for="projeto in projetosFiltrados" 
                            :key="projeto.id" 
                            :projeto="projeto" 
                            @click-status="abrirProgresso(projeto)"
                        />
                    </div>

                    <!-- Table View (List) -->
                    <div v-else class="bg-white border-t border-gray-200">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="px-4 py-3 font-semibold">{{ $t('table.status') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ $t('table.domain') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ $t('table.title') }}</th>
                                    <th class="px-4 py-3 font-semibold">{{ $t('table.updated') }}</th>
                                </tr>
                                <!-- Search Filters Row -->
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="px-4 py-2">
                                        <input type="text" :placeholder="$t('table.search_status')" class="w-full text-xs px-2 py-1 border border-gray-300 rounded shadow-sm focus:ring-1 focus:ring-blue-300 font-normal">
                                    </th>
                                    <th class="px-4 py-2">
                                        <input type="text" :placeholder="$t('table.search_domain')" class="w-full text-xs px-2 py-1 border border-gray-300 rounded shadow-sm focus:ring-1 focus:ring-blue-300 font-normal">
                                    </th>
                                    <th class="px-4 py-2">
                                        <input type="text" :placeholder="$t('table.search_title')" class="w-full text-xs px-2 py-1 border border-gray-300 rounded shadow-sm focus:ring-1 focus:ring-blue-300 font-normal">
                                    </th>
                                    <th class="px-4 py-2">
                                        <input type="text" :placeholder="$t('table.search_updated')" class="w-full text-xs px-2 py-1 border border-gray-300 rounded shadow-sm focus:ring-1 focus:ring-blue-300 font-normal">
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <LinhaTabelaProjeto 
                                    v-for="projeto in projetosFiltrados" 
                                    :key="projeto.id" 
                                    :projeto="projeto"
                                    @click-status="abrirProgresso(projeto)"
                                />
                            </tbody>
                        </table>
                        
                        <!-- Table Footer -->
                        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 text-xs text-gray-500 flex justify-between items-center">
                            <span>{{ $t('table.showing') }} 1 {{ $t('table.to') }} {{ projetosFiltrados.length }} {{ $t('table.of') }} {{ projetosFiltrados.length }} {{ $t('table.entries') }}</span>
                            <button class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 flex items-center gap-1 shadow-sm">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                {{ $t('table.csv') }}
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20 text-gray-500">
                    <p class="text-xl font-light">{{ $t('dashboard.no_websites') }}</p>
                </div>



                <!-- Modal de Progresso -->
                <ModalProgressoRastreador 
                    v-if="projetoSelecionado"
                    :show="mostrarModalProgresso"
                    :projeto="projetoSelecionado"
                    @close="mostrarModalProgresso = false"
                />

            </div>
        </template>
    </AppLayout>
</template>