<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    projeto: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close']);

const tarefa = ref(props.projeto.latest_job);
const enquete = ref(null);
const tempoDecorrido = ref('00:00:00');
const inicioRastreamento = ref(null);
const abaAtiva = ref('details'); // 'details' | 'files'

// Computados visuais
const larguraBarra = computed(() => {
    if (!tarefa.value) return 0;
    return tarefa.value.progress || 0;
});

const urlsPreview = computed(() => {
    if (!tarefa.value || !tarefa.value.preview_urls) {
        const dominio = props.projeto.url.replace(/\/$/, '');
        const data = new Date().toISOString().split('T')[0];
        return [
            { url: `${dominio}/`, priority: '1.0000', freq: 'daily', lastMod: `${data}T12:00:00+00:00` },
        ];
    }
    return tarefa.value.preview_urls;
});

const arquivosMapeados = computed(() => {
    if (!tarefa.value || !tarefa.value.artifacts) return [];
    
    return tarefa.value.artifacts.map(arq => {
        let type = 'xml';
        if (arq.name.endsWith('.txt')) type = 'txt';
        if (arq.name.endsWith('.html')) type = 'html';
        
        return {
            name: arq.name,
            type: type,
            count: tarefa.value.pages_count, // Idealmente cada arq teria seu count, mas usamos o total por enqto
            url: arq.download_url
        };
    });
});

// Lógica de Cronômetro
const atualizarCronometro = () => {
    if (!inicioRastreamento.value) return;
    const agora = new Date();
    const dif = Math.floor((agora - inicioRastreamento.value) / 1000);
    const horas = Math.floor(dif / 3600).toString().padStart(2, '0');
    const minutos = Math.floor((dif % 3600) / 60).toString().padStart(2, '0');
    const segundos = (dif % 60).toString().padStart(2, '0');
    tempoDecorrido.value = `${horas}:${minutos}:${segundos}`;
};

let intervaloCronometro = null;

// Polling recursivo seguro
const agendarProximaBusca = () => {
    if (enquete.value === null) return; // Se foi parado, não agenda
    enquete.value = setTimeout(buscarStatus, 2000);
};

const buscarStatus = async () => {
    try {
        const resposta = await axios.get(route('crawler.show', props.projeto.id));
        tarefa.value = resposta.data;
        console.log('Crawler Status:', tarefa.value.status, tarefa.value.progress);

        // Se finalizou, para tudo
        if (['completed', 'failed', 'cancelled'].includes(tarefa.value.status)) {
            pararEnquete();
        } else {
            // Se continua rodando, agenda o próximo
            agendarProximaBusca();
        }
    } catch (erro) {
        console.error('Erro no modal:', erro);
        // Em caso de erro, tenta de novo após um delay maior para não floodar
        if (enquete.value !== null) {
            enquete.value = setTimeout(buscarStatus, 5000);
        }
    }
};

const iniciarEnquete = () => {
    if (enquete.value) return;
    
    // Marca como ativo (qualquer valor truthy que não seja null)
    enquete.value = true; 
    
    // Inicia cronometro visual
    if (!inicioRastreamento.value) {
        inicioRastreamento.value = new Date(); 
    }
    if (!intervaloCronometro) {
        intervaloCronometro = setInterval(atualizarCronometro, 1000);
    }

    // Dispara a primeira busca Imediatamente
    buscarStatus();
};

const pararEnquete = () => {
    if (enquete.value) clearTimeout(enquete.value);
    enquete.value = null;
    if (intervaloCronometro) {
        clearInterval(intervaloCronometro);
        intervaloCronometro = null;
    }
};

onMounted(() => {
    if (props.show) {
        iniciarEnquete();
    }
});

watch(() => props.show, (novoValor) => {
    if (novoValor) {
        iniciarEnquete();
    } else {
        pararEnquete();
        tempoDecorrido.value = '00:00:00';
        inicioRastreamento.value = null;
        abaAtiva.value = 'details';
    }
});

const iniciarCrawler = async () => {
    try {
        tarefa.value = { status: 'queued', progress: 0, pages_count: 0 }; // Otimistic UI
        await axios.post(route('crawler.store', props.projeto.id));
        // A enquete já vai pegar o status real na próxima chamada
        buscarStatus();
    } catch (erro) {
        console.error('Erro ao iniciar crawler:', erro);
        alert('Falha ao iniciar o crawler. Verifique o console.');
    }
};

onUnmounted(() => {
    pararEnquete();
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-gray-900/60 backdrop-blur-sm transition-opacity overflow-y-auto">
        
        <!-- Cartão Modal -->
        <div class="bg-white rounded-t-lg shadow-2xl w-full max-w-5xl overflow-hidden transform transition-all border border-gray-200 mt-10 mb-10">
            
            <!-- Header Simulado -->
            <div class="bg-[#f0f6fa] border-b border-gray-200 p-4 pb-0 relative">
                 <!-- Botão Fechar -->
                 <button @click="$emit('close')" class="absolute top-4 right-4 px-3 py-1 bg-[#fcf8e3] border border-[#faebcc] text-[#8a6d3b] text-xs font-bold rounded flex items-center gap-1 hover:bg-[#faf2cc] z-20">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    HELP
                </button>

                <div class="flex flex-col items-center">
                    <h2 class="text-2xl font-normal text-[#c0392b] hover:underline cursor-pointer mb-1">
                        {{ projeto.url.replace(/^https?:\/\//, '').replace(/\/$/, '') }}
                    </h2>
                    
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-0.5 bg-white border border-gray-300 text-[10px] font-bold text-gray-500 uppercase">Free 500</span>
                        <span class="text-[10px] font-bold text-green-600 uppercase flex items-center cursor-pointer hover:underline">
                            <span class="text-[8px] mr-1">︽</span> {{ $t('project.upgrade') }} NOW
                        </span>
                    </div>

                    <!-- Menu Topo -->
                    <div class="flex items-center gap-4 text-xs text-gray-600 mb-2">
                         <span class="flex items-center gap-1 cursor-pointer hover:text-gray-900 font-bold border-b-2 border-transparent hover:border-[#c0392b] pb-2">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            My sitemap
                        </span>
                        <span class="cursor-pointer hover:text-gray-900 border-b-2 border-transparent hover:border-[#c0392b] pb-2">Settings</span>
                        <span class="cursor-pointer hover:text-gray-900 font-bold text-green-600 border-b-2 border-transparent pb-2">Upgrade</span>
                        <span class="cursor-pointer font-bold text-gray-800 border-b-2 border-transparent border-t border-l border-r rounded-t bg-white px-2 py-1 -mb-2.5 relative top-0.5 z-10 flex items-center gap-1">
                            <span v-if="['running', 'queued'].includes(tarefa?.status)" class="animate-spin">🔁</span>
                            <span v-else>✔</span>
                             Update sitemap 
                             <span v-if="['running', 'queued'].includes(tarefa?.status)" class="text-red-500 text-[8px]">●</span>
                        </span>
                        <span class="cursor-pointer hover:text-gray-900 border-b-2 border-transparent hover:border-[#c0392b] pb-2">Submit sitemap</span>
                        <span class="cursor-pointer hover:text-gray-900 border-b-2 border-transparent hover:border-[#c0392b] pb-2">Reports</span>
                    </div>
                </div>
            </div>

            <!-- Area de Abas (Só aparece se concluído) -->
            <div v-if="tarefa?.status === 'completed'" class="flex bg-white px-8 pt-6 border-b border-gray-200">
                <button 
                    @click="abaAtiva = 'details'"
                    :class="['px-4 py-2 text-sm font-bold uppercase border-t border-l border-r rounded-t transition-colors mr-2', abaAtiva === 'details' ? 'bg-white text-[#c0392b] border-gray-200 border-b-white -mb-px relative z-10' : 'bg-gray-50 text-gray-500 border-transparent hover:bg-gray-100']"
                >
                    <span class="mr-1">⁕</span> Sitemap Details
                </button>
                <button 
                    @click="abaAtiva = 'files'"
                    :class="['px-4 py-2 text-sm font-bold uppercase border-t border-l border-r rounded-t transition-colors', abaAtiva === 'files' ? 'bg-white text-[#c0392b] border-gray-200 border-b-white -mb-px relative z-10' : 'bg-gray-50 text-gray-500 border-transparent hover:bg-gray-100']"
                >
                    <span class="mr-1">☁</span> Sitemap Files
                </button>
            </div>

            <!-- Corpo Principal -->
            <div class="bg-white p-8 border-t border-gray-200 relative min-h-[400px]">

                <!-- 1. ESTADO: EM PROGRESSO (Igual anterior) -->
                <div v-if="['running', 'queued'].includes(tarefa?.status)" class="flex flex-col items-center justify-center mt-4">
                     <!-- Barra Topo Status -->
                    <div class="absolute top-0 left-0 right-0 h-8 bg-[#e8f6fc] border-b border-[#bce8f1] flex items-center px-4 justify-between">
                        <div class="flex items-center gap-2 text-xs text-[#31708f] font-bold">
                            <span class="flex items-center gap-2">
                                <svg class="animate-spin w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                {{ tarefa?.pages_count || 0 }}/500 Update in progress
                            </span>
                        </div>
                    </div>

                    <h3 class="text-[#007da0] text-lg font-bold uppercase mb-2 mt-6">IN PROGRESS</h3>
                    <p class="text-gray-500 text-sm mb-6 text-center max-w-lg">
                        Crawling process has been already scheduled to start. Please wait until it is completed. <br>
                        You will receive an email notification when sitemap is created.
                    </p>

                    <div class="w-full max-w-2xl bg-[#e8f6fc] border border-[#bce8f1] p-0 mb-8">
                        <div class="p-3 text-center border-b border-[#bce8f1] bg-[#d9edf7] relative">
                            <span class="text-[#31708f] font-bold text-sm">Update in progress</span>
                            <span class="ml-2 px-1.5 py-0.5 bg-[#f0ad4e] text-white text-[10px] font-bold rounded cursor-not-allowed">▐▌ PAUSE</span>
                        </div>
                        
                        <div class="p-6 text-center text-[#31708f] text-sm leading-relaxed">
                            Time elapsed: {{ tempoDecorrido }}, Pages processed: {{ tarefa?.pages_count || 0 }} ({{ tarefa?.pages_count || 0 }} added in sitemap)<br>
                            <span class="text-xs opacity-75">Queued: 128, Depth level: 1, Next level: 64</span><br>
                            Current page: <span class="font-bold text-[#c0392b]">/</span>
                        </div>

                        <div class="h-2 w-full bg-[#d0e6f0] relative">
                             <div class="h-full bg-[#007da0] transition-all duration-500" :style="{ width: larguraBarra + '%' }"></div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 w-full max-w-xl">
                        <h4 class="text-[#007da0] font-bold uppercase text-sm border-b border-gray-200 pb-1 mb-2">Crawler Bot Controls</h4>
                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <button class="px-4 py-1.5 bg-[#f0ad4e] hover:bg-[#ec971f] text-white font-bold rounded text-xs w-24 flex justify-center items-center gap-1 shadow-sm">▐▌ PAUSE</button> click to put crawling on pause
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <button class="px-4 py-1.5 bg-[#d9534f] hover:bg-[#c9302c] text-white font-bold rounded text-xs w-24 flex justify-center items-center gap-1 shadow-sm" @click="$emit('close')">↻ RESET</button> click to reset ALL crawling progress
                        </div>
                    </div>
                </div>

                <!-- 2. ESTADO: COMPLETED - ABA DETAILS -->
                <div v-else-if="tarefa?.status === 'completed' && abaAtiva === 'details'">
                    
                    <div class="text-xs font-bold text-green-600 uppercase mb-4 tracking-wide">
                        SITEMAP UPDATED JUST NOW
                    </div>

                    <!-- Cards Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <!-- Pages Card -->
                        <div class="border rounded shadow-sm overflow-hidden">
                            <div class="bg-[#5bc0de] text-white text-center py-1 font-bold uppercase text-lg">PAGES</div>
                            <div class="p-4 flex justify-between items-center bg-white">
                                <div class="text-center">
                                    <span class="text-3xl font-bold text-[#31708f]">{{ tarefa?.pages_count }}</span>
                                    <span class="text-xs text-green-500 font-bold ml-1">▲ {{ tarefa?.pages_count }}</span>
                                    <div class="text-[10px] font-bold text-[#31708f] uppercase mt-1">INDEXED</div>
                                    <a href="#" class="text-[10px] text-[#c0392b] border border-[#c0392b] rounded px-1 mt-1 inline-block hover:bg-[#c0392b] hover:text-white transition">☑ VIEW SITEMAP</a>
                                </div>
                                <div class="text-right text-xs text-gray-600 space-y-1">
                                    <div><span class="font-bold">{{ tarefa?.pages_count + 130 }}</span> discovered</div>
                                    <div class="text-red-400"><span class="font-bold">130</span> skipped »</div>
                                    <div class="text-green-600"><span class="font-bold">{{ tarefa?.pages_count }}</span> added »</div>
                                </div>
                            </div>
                        </div>

                        <!-- Images Card -->
                        <div class="border rounded shadow-sm overflow-hidden">
                            <div class="bg-[#d2d6de] text-white text-center py-1 font-bold uppercase text-lg">IMAGES</div>
                            <div class="p-4 flex justify-between items-center bg-white">
                                <div class="text-center w-full">
                                    <span class="text-3xl font-bold text-[#31708f]">{{ tarefa?.images_count || 0 }}</span>
                                    <div class="text-[10px] font-bold text-[#31708f] uppercase mt-1">INDEXED</div>
                                    <a v-if="tarefa?.images_count > 0" href="#" class="text-[10px] text-[#c0392b] border border-[#c0392b] rounded px-1 mt-1 inline-block hover:bg-[#c0392b] hover:text-white transition">☑ VIEW SITEMAP</a>
                                </div>
                            </div>
                        </div>

                        <!-- Videos Card -->
                        <div class="border rounded shadow-sm overflow-hidden">
                            <div class="bg-[#d2d6de] text-white text-center py-1 font-bold uppercase text-lg">VIDEOS</div>
                            <div class="p-4 flex justify-between items-center bg-white">
                                <div class="text-center w-full">
                                    <span class="text-3xl font-bold text-[#31708f]">{{ tarefa?.videos_count || 0 }}</span>
                                    <div class="text-[10px] font-bold text-[#31708f] uppercase mt-1">INDEXED</div>
                                    <a v-if="tarefa?.videos_count > 0" href="#" class="text-[10px] text-[#c0392b] border border-[#c0392b] rounded px-1 mt-1 inline-block hover:bg-[#c0392b] hover:text-white transition">☑ VIEW SITEMAP</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="bg-[#007da0] hover:bg-[#006480] text-white font-bold text-xs uppercase py-2 px-4 rounded shadow-sm mb-8 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Il. OPEN DETAILED SITEMAP REPORT
                    </button>

                    <h4 class="text-xl font-light text-gray-600 mb-4">Sitemap Quick Preview</h4>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 border-collapse">
                            <thead class="bg-white border-b border-gray-200">
                                <tr>
                                    <th class="py-2 font-bold uppercase text-xs">URL</th>
                                    <th class="py-2 font-bold uppercase text-xs text-right">Last Mod</th>
                                    <th class="py-2 font-bold uppercase text-xs text-right">Priority</th>
                                    <th class="py-2 font-bold uppercase text-xs text-right">Freq</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(item, i) in urlsPreview" :key="i" class="hover:bg-gray-50">
                                    <td class="py-2 text-[#c0392b]">{{ item.url }}</td>
                                    <td class="py-2 text-right text-gray-400 text-xs">{{ item.lastMod }}</td>
                                    <td class="py-2 text-right text-gray-400 text-xs">{{ item.priority }}</td>
                                    <td class="py-2 text-right text-gray-400 text-xs">{{ item.freq }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="text-right mt-2 text-xs text-gray-400">Showing 1 to 6 of {{ tarefa?.pages_count }} entries</div>
                    </div>
                </div>

                <!-- 3. ESTADO: COMPLETED - ABA FILES -->
                <div v-else-if="tarefa?.status === 'completed' && abaAtiva === 'files'">
                    
                    <div class="bg-gray-50 border border-gray-200 rounded p-4 mb-6 text-sm text-gray-600">
                        <span class="font-bold">NOTE:</span> You can also create Images, Videos, News sitemap and RSS feed for your website. <br>
                        You need to <a href="#" class="font-bold text-[#337ab7] border-b border-[#337ab7]">upgrade</a> your account for that.
                    </div>

                    <table class="w-full text-left border-t border-b border-gray-200">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="py-3 font-bold text-gray-700 text-sm text-center">View sitemap</th>
                                <th class="py-3 font-bold text-gray-700 text-sm text-center">Download</th>
                                <th class="py-3 font-bold text-gray-700 text-sm text-center">Convert</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- Items Dinamicos -->
                            <tr v-for="arq in arquivosMapeados" :key="arq.name" class="hover:bg-gray-50">
                                <td class="py-3 px-4 text-center">
                                    <a :href="arq.url" target="_blank" class="flex items-center justify-start gap-2 text-[#c0392b] text-sm hover:underline pl-10">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                        {{ arq.name }} <span class="text-gray-400">({{ arq.count }})</span>
                                    </a>
                                </td>
                                <td class="py-3 text-center">
                                    <a :href="arq.url" download class="inline-flex items-center gap-1 text-[#c0392b] hover:font-bold text-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        {{ arq.type === 'xml' ? '.xml' : (arq.type === 'html' ? '.html' : '.txt') }}
                                    </a>
                                    <span class="text-[#c0392b] ml-4 hover:font-bold cursor-pointer text-sm inline-flex items-center gap-1">
                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        .gz
                                    </span>
                                </td>
                                <td class="py-3 text-center">
                                    <span v-if="arq.type === 'xml'" class="text-[#c0392b] hover:font-bold cursor-pointer text-sm inline-flex items-center gap-1">
                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        .csv
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-8 text-center">
                         <button class="bg-[#007da0] hover:bg-[#006480] text-white font-bold py-3 px-6 rounded shadow-sm text-sm flex items-center justify-center gap-2 mx-auto">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            DOWNLOAD ALL SITEMAPS IN A ZIP FILE
                        </button>
                    </div>

                </div>

                <!-- 4. ESTADO: PRONTO / NENHUM JOB -->
                <div v-else-if="!tarefa?.status" class="flex flex-col items-center justify-center py-20 text-center">
                     <div class="mb-6">
                        <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                     </div>
                     <h3 class="text-xl font-bold text-gray-700 mb-2">Ready to Crawl</h3>
                     <p class="text-gray-500 mb-8 max-w-md mx-auto">
                        Project configuration is saved. You can now start the crawler to generate your sitemap.
                     </p>
                     <button @click="iniciarCrawler" class="bg-[#007da0] hover:bg-[#006480] text-white font-bold py-3 px-8 rounded shadow-lg transition-transform transform hover:scale-105 flex items-center gap-2 mx-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        START CRAWLING NOW
                    </button>
                </div>

                <!-- 5. ESTADO: INICIALIZANDO / ERRO -->
                <div v-else class="flex flex-col items-center justify-center py-20">
                     <div v-if="!tarefa" class="text-center">
                        <svg class="animate-spin w-10 h-10 text-blue-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-500 font-semibold mb-2">Iniciando serviço de rastreamento...</p>
                        <p class="text-xs text-red-400 max-w-xs mx-auto">
                            Se demorar muito, verifique se a API do Crawler está online. <br>
                            Tente recarregar a página.
                        </p>
                     </div>
                </div>

            </div>
        </div>
    </div>
</template>
