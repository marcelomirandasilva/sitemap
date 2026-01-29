<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";
import { trans as t } from "laravel-vue-i18n";
import axios from 'axios';
import UrlDataTable from '@/Components/Project/UrlDataTable.vue';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    latest_job: {
        type: Object,
        default: null,
    },
    preview_urls: {
        type: Array,
        default: () => []
    }
});

const abaAtiva = ref("details");
const carregando = ref(false);

// Estado reativo inicializado com props (cache/ssr)
const tarefa = ref(props.latest_job || {});
const listaUrls = ref(props.preview_urls || []);

// Computado para exibir (usa o estado reativo)
const urlsPreview = computed(() => listaUrls.value);

const arquivosMapeados = computed(() => {
    if (!tarefa.value || !tarefa.value.artifacts) return [];

    // Mapeamento idêntico ao anterior...
    const xml = tarefa.value.artifacts.find((a) => a.name && a.name.endsWith(".xml"));
    const txt = tarefa.value.artifacts.find((a) => a.name && a.name.endsWith(".txt"));

    const lista = [];
    if (xml) {
        lista.push({
            name: xml.name,
            type: "xml",
            count: tarefa.value.pages_count,
            url: xml.download_url,
        });
        lista.push({
            name: xml.name.replace(".xml", ".html"),
            type: "html",
            count: tarefa.value.pages_count,
            url: xml.download_url,
        });
        lista.push({
            name: xml.name + '.gz',
            type: 'gz',
            count: tarefa.value.pages_count,
            url: xml.download_url
        });
    }
    if (txt) {
        lista.push({
            name: txt.name,
            type: "txt",
            count: tarefa.value.pages_count,
            url: txt.download_url,
        });
    }
    return lista;
});

const statusColor = computed(() => {
    switch (tarefa.value.status) {
        case 'completed': return 'text-green-600 bg-green-50 border-green-200';
        case 'failed': return 'text-red-600 bg-red-50 border-red-200';
        case 'running': return 'text-blue-600 bg-blue-50 border-blue-200';
        default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
});

// Busca dados atualizados (Async Lazy Load)
const buscarDetalhesJob = async () => {
    if (!props.project.id) return;

    carregando.value = true;
    try {
        const response = await axios.get(route('crawler.show', props.project.id));
        const data = response.data;

        // Atualiza estado local
        tarefa.value = { ...tarefa.value, ...data };

        if (data.preview_urls) {
            listaUrls.value = data.preview_urls;
        }
    } catch (error) {
        console.error("Erro ao buscar detalhes atualizados:", error);
    } finally {
        carregando.value = false;
    }
};

onMounted(() => {
    // Se não tiver urls de preview ou se o status for inconclusivo, busca atualização
    if (listaUrls.value.length === 0 || ['queued', 'running'].includes(tarefa.value.status)) {
        buscarDetalhesJob();
    }
});

const downloadUrl = computed(() => {
    if (!tarefa.value || !tarefa.value.artifacts) return null;
    const artifact = tarefa.value.artifacts.find(a => a.name && a.name.endsWith('.xml')) || 
                     tarefa.value.artifacts.find(a => a.name && a.name.endsWith('.txt'));
    return artifact ? artifact.download_url : null;
});
</script>

<template>

    <Head :title="project.name || $t('project.details')" />

    <AppLayout>
        <template #hero>
            <div class="bg-blue-50 border-b border-blue-100 py-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4">
                            <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-600 transition"
                                :title="$t('project.back_dashboard')">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                            </Link>
                            <div>
                                <h1 class="text-3xl font-light text-gray-700">
                                    {{ project.name || project.url }}
                                </h1>
                                <a :href="project.url" target="_blank"
                                    class="text-sm text-blue-500 hover:underline flex items-center gap-1">
                                    {{ project.url }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                        </path>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                :class="['px-3 py-1 rounded-full text-xs font-bold uppercase border flex items-center gap-1', statusColor]">
                                <span v-if="['running', 'queued'].includes(tarefa.status)"
                                    class="animate-spin w-2 h-2 rounded-full border-2 border-current border-t-transparent"></span>
                                <span v-else class="w-2 h-2 rounded-full bg-current"></span>
                                {{ tarefa.status ? $t('crawler.status.' + tarefa.status) : 'No Job' }}
                            </span>
                            <button
                                class="bg-[#007da0] hover:bg-[#006480] text-white font-bold py-2 px-4 rounded text-sm uppercase shadow-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                {{ $t('project.recrawl') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                <!-- Alerta se falho -->
                <div v-if="tarefa.status === 'failed'" class="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                {{ $t('project.crawling_failed') }}. <span v-if="tarefa.message">{{ tarefa.message
                                    }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas (Cards) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Pages Card -->
                    <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
                        <div class="bg-[#5bc0de] text-white text-center py-2 font-bold uppercase tracking-wider">{{
                            $t('project.pages_discovered') }}</div>
                        <div class="p-6 flex justify-between items-center">
                            <div>
                                <span class="text-4xl font-bold text-[#31708f]">{{ tarefa.pages_count || 0 }}</span>
                                <div class="text-xs font-bold text-gray-400 uppercase mt-1">{{
                                    $t('project.total_indexed') }}</div>
                            </div>
                            <div class="text-right text-xs text-gray-600 space-y-1">
                                <div><span class="font-bold">{{ (tarefa.pages_count || 0) + 12 }}</span> {{
                                    $t('project.stat_discovered') }}</div>
                                <div class="text-green-600"><span class="font-bold">{{ tarefa.pages_count || 0 }}</span>
                                    {{ $t('project.new_added') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Images Card -->
                    <div class="bg-white border rounded-lg shadow-sm overflow-hidden opacity-75">
                        <div class="bg-[#d2d6de] text-gray-600 text-center py-2 font-bold uppercase tracking-wider">{{
                            $t('project.images') }}</div>
                        <div class="p-6 text-center">
                            <span class="text-4xl font-bold text-gray-400">0</span>
                            <div class="text-xs font-bold text-gray-400 uppercase mt-1">{{ $t('project.stat_indexed') }}
                            </div>
                            <span
                                class="mt-2 inline-block bg-green-500 text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold">{{
                                $t('project.pro_feature') }}</span>
                        </div>
                    </div>

                    <!-- Videos Card -->
                    <div class="bg-white border rounded-lg shadow-sm overflow-hidden opacity-75">
                        <div class="bg-[#d2d6de] text-gray-600 text-center py-2 font-bold uppercase tracking-wider">{{
                            $t('project.videos') }}</div>
                        <div class="p-6 text-center">
                            <span class="text-4xl font-bold text-gray-400">0</span>
                            <div class="text-xs font-bold text-gray-400 uppercase mt-1">{{ $t('project.stat_indexed') }}
                            </div>
                            <span
                                class="mt-2 inline-block bg-green-500 text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold">{{
                                $t('project.pro_feature') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Conteúdo Principal com Abas -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden min-h-[500px]">
                    <!-- Headers das Abas -->
                    <div class="flex border-b border-gray-200 bg-gray-50 px-6 pt-4">
                        <button @click="abaAtiva = 'details'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all mr-1 translate-y-[1px]', abaAtiva === 'details' ? 'bg-white text-[#c0392b] border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">⁕</span> {{ $t('project.sitemap_overview') }}
                        </button>
                        <button @click="abaAtiva = 'files'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all translate-y-[1px]', abaAtiva === 'files' ? 'bg-white text-[#c0392b] border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">☁</span> {{ $t('project.download_files') }}
                        </button>
                    </div>

                    <!-- Conteúdo da Aba -->
                    <div class="p-8">

                        <!-- ABA DETAILS -->
                        <div v-if="abaAtiva === 'details'">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-bold text-gray-700">{{ $t('project.recent_urls') }}</h3>
                                <div class="text-sm text-gray-500">
                                     <a v-if="downloadUrl" :href="downloadUrl" target="_blank" class="text-blue-500 hover:underline">
                                        {{ $t('project.download_full_list') }}
                                    </a>
                                </div>
                            </div>

                            <!-- Tabela Paginada via API -->
                            <UrlDataTable :project-id="project.id" />
                        </div>
                        

                        <!-- ABA FILES -->
                        <div v-else-if="abaAtiva === 'files'">
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-8 flex items-start gap-3">
                                <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm text-blue-800">
                                    <p class="font-bold mb-1">{{ $t('project.integration_instructions') }}</p>
                                    <p>{{ $t('project.integration_text') }}</p>
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-gray-700 mb-4">{{ $t('project.available_downloads') }}
                            </h3>

                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="arq in arquivosMapeados" :key="arq.name"
                                    class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-300 hover:shadow-md transition bg-white group">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center text-gray-500 font-bold uppercase text-xs border border-gray-200">
                                            {{ arq.type }}
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 group-hover:text-blue-600 transition">{{
                                                arq.name }}</h4>
                                            <p class="text-xs text-gray-500">{{ arq.count }} {{
                                                $t('project.urls_inside') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a :href="arq.url" target="_blank" class="text-gray-400 hover:text-gray-600 p-2"
                                            :title="$t('project.view_action')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                        <a :href="arq.url" download
                                            class="bg-[#c0392b] hover:bg-[#a93226] text-white font-bold py-2 px-4 rounded text-sm shadow flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4">
                                                </path>
                                            </svg>
                                            {{ $t('project.download_action') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <Link :href="route('dashboard')"
                    class="text-gray-500 hover:text-blue-600 transition flex items-center justify-end gap-2 mr-4 mt-4 "
                    :title="$t('project.back_dashboard')">
                    <div
                        class="p-1.5 rounded-full hover:bg-white hover:shadow-sm border border-transparent hover:border-gray-200 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-sm uppercase hidden md:inline-block">{{ $t('project.back_dashboard')
                        }}</span>
                </Link>
            </div>
        </template>
    </AppLayout>
</template>
