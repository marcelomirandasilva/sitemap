<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { trans as t } from 'laravel-vue-i18n';
import axios from 'axios';
import Swal from 'sweetalert2';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    projeto: {
        type: Object,
        required: true
    },
    userPlan: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'update:job']);

const tarefa = ref(props.projeto.ultimo_job || props.projeto.latest_job || null);
const enquete = ref(null);
const tempoDecorrido = ref('00:00:00');
const inicioRastreamento = ref(null);
const abaAtiva = ref('details');
const processandoAcao = ref(false);
let intervaloCronometro = null;

const statusAtivo = computed(() => ['queued', 'running'].includes(tarefa.value?.status));
const limitePlano = computed(() => props.userPlan?.max_pages ?? props.projeto?.max_pages ?? 500);
const limiteEfetivoProjeto = computed(() => {
    const limiteProjeto = props.projeto?.max_pages ?? limitePlano.value;
    return Math.min(limiteProjeto, limitePlano.value);
});
const badgePlano = computed(() => {
    const nomePlano = props.userPlan?.name ?? 'Plano';
    return `${nomePlano} ${limitePlano.value}`;
});

const larguraBarra = computed(() => {
    if (!tarefa.value) return 0;
    return Math.max(4, Math.floor(tarefa.value.progress || 0));
});

const paginasAdicionadas = computed(() => {
    if (!tarefa.value) return 0;
    return tarefa.value.pages_count ?? (tarefa.value.status === 'completed' ? (tarefa.value.urls_found ?? 0) : 0);
});

const paginasPuladas = computed(() => {
    if (!tarefa.value) return 0;
    return tarefa.value.urls_excluded ?? 0;
});

const paginasDescobertasAtuais = computed(() => {
    if (!tarefa.value) return 0;
    if (statusAtivo.value) return tarefa.value.urls_found ?? 0;
    return paginasAdicionadas.value + paginasPuladas.value;
});

const paginasDescobertas = computed(() => paginasDescobertasAtuais.value);
const contarUrlsPorArtefato = (artifactName) => {
    const normalized = (artifactName || '').toLowerCase();

    if (normalized.includes('image')) {
        return tarefa.value?.images_count ?? 0;
    }

    if (normalized.includes('video')) {
        return tarefa.value?.videos_count ?? 0;
    }

    return paginasAdicionadas.value;
};

const progressoPercentual = computed(() => {
    if (!tarefa.value) return 0;
    return Math.floor(tarefa.value.progress || 0);
});

const arquivosMapeados = computed(() => {
    if (!Array.isArray(tarefa.value?.artifacts)) return [];

    return tarefa.value.artifacts.map((arquivo) => ({
        name: arquivo.name,
        type: arquivo.name?.split('.').pop()?.toUpperCase() || 'FILE',
        count: contarUrlsPorArtefato(arquivo.name),
        url: arquivo.download_url,
    }));
});

const atualizarCronometro = () => {
    if (!inicioRastreamento.value) return;

    const agora = new Date();
    const dif = Math.floor((agora - inicioRastreamento.value) / 1000);
    const horas = Math.floor(dif / 3600).toString().padStart(2, '0');
    const minutos = Math.floor((dif % 3600) / 60).toString().padStart(2, '0');
    const segundos = (dif % 60).toString().padStart(2, '0');
    tempoDecorrido.value = `${horas}:${minutos}:${segundos}`;
};

const pararEnquete = () => {
    if (enquete.value) clearTimeout(enquete.value);
    enquete.value = null;

    if (intervaloCronometro) {
        clearInterval(intervaloCronometro);
        intervaloCronometro = null;
    }
};

const iniciarCronometro = () => {
    if (!inicioRastreamento.value) {
        inicioRastreamento.value = tarefa.value?.started_at ? new Date(tarefa.value.started_at) : new Date();
    }

    if (!intervaloCronometro) {
        atualizarCronometro();
        intervaloCronometro = setInterval(atualizarCronometro, 1000);
    }
};

const agendarProximaBusca = () => {
    if (!props.show || !statusAtivo.value) return;
    enquete.value = setTimeout(buscarStatus, 2500);
};

const buscarStatus = async () => {
    try {
        const resposta = await axios.get(route('projects.status', props.projeto.id) + '?t=' + Date.now());
        tarefa.value = resposta.data;
        emit('update:job', tarefa.value);

        if (statusAtivo.value) {
            iniciarCronometro();
            agendarProximaBusca();
        } else {
            pararEnquete();
        }
    } catch (erro) {
        console.error('Erro no modal:', erro);

        if (props.show && statusAtivo.value) {
            enquete.value = setTimeout(buscarStatus, 5000);
        }
    }
};

const iniciarEnquete = () => {
    if (!props.show || !statusAtivo.value) return;
    pararEnquete();
    iniciarCronometro();
    buscarStatus();
};

const iniciarCrawler = async () => {
    if (processandoAcao.value) return;

    processandoAcao.value = true;

    try {
        const resposta = await axios.post(route('projects.crawl', props.projeto.id));
        tarefa.value = {
            ...(tarefa.value || {}),
            status: resposta.data.status ?? 'queued',
            external_job_id: resposta.data.job_id ?? null,
            progress: 0,
            pages_count: 0,
            urls_found: 0,
            urls_crawled: 0,
            urls_excluded: 0,
            queue_size: 0,
            current_depth: 0,
            message: resposta.data.message ?? null,
            completed_at: null,
            started_at: new Date().toISOString(),
            artifacts: [],
        };
        emit('update:job', tarefa.value);
        inicioRastreamento.value = new Date();
        iniciarEnquete();
    } catch (erro) {
        console.error('Erro ao iniciar crawler:', erro);

        if (erro.response?.status === 409) {
            await buscarStatus();
            await Swal.fire({
                title: t('crawler.update_in_progress'),
                text: erro.response?.data?.message ?? t('crawler.in_progress_desc'),
                icon: 'info',
            });
        } else {
            await Swal.fire({
                title: t('common.error'),
                text: erro.response?.data?.message ?? t('crawler.error_generic'),
                icon: 'error',
            });
        }
    } finally {
        processandoAcao.value = false;
    }
};

const cancelarCrawler = async ({ silent = false } = {}) => {
    if (processandoAcao.value || !statusAtivo.value) return false;

    if (!silent) {
        const confirmacao = await Swal.fire({
            title: t('crawler.cancel_confirm_title'),
            text: t('crawler.cancel_confirm_text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d97706',
            cancelButtonColor: '#6b7280',
            confirmButtonText: t('crawler.cancel'),
            cancelButtonText: t('project.delete_cancel_btn'),
        });

        if (!confirmacao.isConfirmed) {
            return false;
        }
    }

    processandoAcao.value = true;

    try {
        const resposta = await axios.post(route('projects.crawl.cancel', props.projeto.id));
        tarefa.value = {
            ...(tarefa.value || {}),
            status: resposta.data.status ?? 'cancelled',
            message: resposta.data.message ?? null,
            completed_at: resposta.data.completed_at ?? new Date().toISOString(),
        };
        emit('update:job', tarefa.value);
        pararEnquete();
        return true;
    } catch (erro) {
        console.error('Erro ao cancelar crawler:', erro);
        await buscarStatus();

        if (!silent) {
            await Swal.fire({
                title: t('common.error'),
                text: erro.response?.data?.message ?? t('crawler.error_generic'),
                icon: 'error',
            });
        }

        return false;
    } finally {
        processandoAcao.value = false;
    }
};

const resetarCrawler = async () => {
    if (processandoAcao.value) return;

    const confirmacao = await Swal.fire({
        title: t('crawler.restart_confirm_title'),
        text: t('crawler.restart_confirm_text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: t('crawler.reset'),
        cancelButtonText: t('project.delete_cancel_btn'),
    });

    if (!confirmacao.isConfirmed) {
        return;
    }

    if (statusAtivo.value) {
        const cancelado = await cancelarCrawler({ silent: true });

        if (!cancelado) {
            return;
        }
    }

    pararEnquete();
    tempoDecorrido.value = '00:00:00';
    inicioRastreamento.value = null;
    await iniciarCrawler();
};

watch(() => props.show, (novoValor) => {
    if (novoValor && statusAtivo.value) {
        iniciarEnquete();
    } else if (!novoValor) {
        pararEnquete();
        tempoDecorrido.value = '00:00:00';
        inicioRastreamento.value = null;
        abaAtiva.value = 'details';
    }
});

watch(() => props.projeto, (novoProjeto) => {
    tarefa.value = novoProjeto?.ultimo_job || novoProjeto?.latest_job || tarefa.value;

    if (statusAtivo.value && props.show) {
        iniciarEnquete();
    }
}, { deep: true });

onMounted(() => {
    if (props.show && statusAtivo.value) {
        iniciarEnquete();
    }
});

onUnmounted(() => {
    pararEnquete();
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-gray-900/60 backdrop-blur-sm overflow-y-auto">
        <div class="bg-white rounded-t-lg shadow-2xl w-full max-w-5xl overflow-hidden border border-gray-200 mt-10 mb-10">
            <div class="bg-[#f0f6fa] border-b border-gray-200 p-4 pb-0 relative">
                <button @click="$emit('close')" class="absolute top-4 right-4 px-3 py-1 bg-[#fcf8e3] border border-[#faebcc] text-[#8a6d3b] text-xs font-bold rounded flex items-center gap-1 hover:bg-[#faf2cc] z-20">
                    {{ $t('modal.close') }}
                </button>

                <div class="flex flex-col items-center w-full">
                    <h2 class="text-2xl font-normal text-accent-600 hover:underline cursor-pointer mb-1 max-w-[90%] truncate" :title="projeto.url">
                        {{ projeto.url.replace(/^https?:\/\//, '').replace(/\/$/, '') }}
                    </h2>

                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-0.5 bg-white border border-gray-300 text-[10px] font-bold text-gray-500 uppercase">{{ badgePlano }}</span>
                        <Link :href="route('subscription.index')" class="text-[10px] font-bold text-green-600 uppercase hover:underline">
                            {{ $t('project.upgrade') }}
                        </Link>
                    </div>

                    <div class="flex items-center gap-4 text-xs text-gray-600 mb-2">
                        <span class="font-bold border-b-2 border-transparent hover:border-accent-600 pb-2">{{ $t('modal.my_sitemap') }}</span>
                        <Link :href="route('subscription.index')" class="font-bold text-green-600 border-b-2 border-transparent pb-2">{{ $t('modal.upgrade') }}</Link>
                        <span class="font-bold text-gray-800 border-b-2 border-transparent border-t border-l border-r rounded-t bg-white px-2 py-1 -mb-2.5 relative top-0.5 z-10">
                            {{ $t('modal.update_sitemap') }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="tarefa?.status === 'completed'" class="flex bg-white px-8 pt-6 border-b border-gray-200">
                <button
                    @click="abaAtiva = 'details'"
                    :class="['px-4 py-2 text-sm font-bold uppercase border-t border-l border-r rounded-t transition-colors mr-2', abaAtiva === 'details' ? 'bg-white text-accent-600 border-gray-200 border-b-white -mb-px relative z-10' : 'bg-gray-50 text-gray-500 border-transparent hover:bg-gray-100']"
                >
                    {{ $t('project.sitemap_overview') }}
                </button>
                <button
                    @click="abaAtiva = 'files'"
                    :class="['px-4 py-2 text-sm font-bold uppercase border-t border-l border-r rounded-t transition-colors', abaAtiva === 'files' ? 'bg-white text-accent-600 border-gray-200 border-b-white -mb-px relative z-10' : 'bg-gray-50 text-gray-500 border-transparent hover:bg-gray-100']"
                >
                    {{ $t('project.download_files') }}
                </button>
            </div>

            <div class="bg-white p-8 border-t border-gray-200 relative min-h-[400px]">
                <div v-if="statusAtivo" class="flex flex-col items-center justify-center mt-4">
                    <div class="absolute top-0 left-0 right-0 h-8 bg-[#e8f6fc] border-b border-[#bce8f1] flex items-center px-4 justify-between">
                        <div class="flex items-center gap-2 text-xs text-primary-800 font-bold">
                            {{ progressoPercentual }}% {{ $t('crawler.update_in_progress') }}
                        </div>
                        <button
                            @click="cancelarCrawler"
                            :disabled="processandoAcao"
                            class="px-2 py-1 bg-warning-500 hover:bg-warning-600 disabled:opacity-60 text-white text-[10px] font-bold rounded"
                        >
                            {{ processandoAcao ? $t('crawler.cancelling') : $t('crawler.cancel') }}
                        </button>
                    </div>

                    <h3 class="text-primary-600 text-lg font-bold uppercase mb-2 mt-6">{{ $t('crawler.in_progress_title') }}</h3>
                    <p class="text-gray-500 text-sm mb-6 text-center max-w-lg">
                        {{ $t('crawler.in_progress_desc') }}<br>
                        {{ $t('crawler.email_notification') }}
                    </p>

                    <div class="w-full max-w-3xl border border-[#bce8f1] rounded overflow-hidden">
                        <div class="p-4 bg-[#d9edf7] text-center text-primary-800 text-sm leading-relaxed">
                            <div>
                                {{ $t('crawler.time_elapsed') }}: <span class="font-bold">{{ tempoDecorrido }}</span>,
                                {{ $t('crawler.pages_processed') }}: <span class="font-bold">{{ tarefa?.urls_crawled || 0 }}</span>
                            </div>
                            <div class="text-xs opacity-75 mt-1">
                                {{ $t('crawler.urls_discovered') }}: <span class="font-bold">{{ paginasDescobertasAtuais }}</span>,
                                {{ $t('crawler.project_limit') }}: <span class="font-bold">{{ limiteEfetivoProjeto }}</span>
                            </div>
                            <div v-if="paginasDescobertasAtuais > limiteEfetivoProjeto" class="text-xs text-primary-700 mt-1">
                                {{ $t('crawler.limit_notice', { count: limiteEfetivoProjeto }) }}
                            </div>
                            <div class="text-xs opacity-75 mt-1">
                                {{ $t('crawler.queued') }}: <span class="font-bold">{{ tarefa?.queue_size || 0 }}</span>,
                                {{ $t('crawler.depth_level') }}: <span class="font-bold">{{ tarefa?.current_depth || 0 }}</span>
                            </div>
                            <div class="mt-2 text-xs break-all">
                                {{ $t('crawler.current_page') }}:
                                <span class="font-bold text-accent-600">{{ tarefa?.current_url || '/' }}</span>
                            </div>
                        </div>

                        <div class="h-2 w-full bg-[#d0e6f0]">
                            <div class="h-full bg-primary-600 transition-all duration-500" :style="{ width: larguraBarra + '%' }"></div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 w-full max-w-3xl mt-6">
                        <h4 class="text-primary-600 font-bold uppercase text-sm border-b border-gray-200 pb-1">{{ $t('crawler.bot_controls') }}</h4>
                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <button
                                @click="cancelarCrawler"
                                :disabled="processandoAcao"
                                class="px-4 py-1.5 bg-warning-500 hover:bg-warning-600 disabled:opacity-60 text-white font-bold rounded text-xs w-28 flex justify-center items-center shadow-sm transition-colors"
                            >
                                {{ processandoAcao ? $t('crawler.cancelling') : $t('crawler.cancel') }}
                            </button>
                            {{ $t('crawler.cancel_help') }}
                        </div>
                        <div class="flex items-center gap-3 text-sm text-gray-500">
                            <button
                                @click="resetarCrawler"
                                :disabled="processandoAcao"
                                class="px-4 py-1.5 bg-[#d9534f] hover:bg-[#c9302c] disabled:opacity-60 text-white font-bold rounded text-xs w-28 flex justify-center items-center shadow-sm transition-colors"
                            >
                                {{ $t('crawler.reset') }}
                            </button>
                            {{ $t('crawler.reset_help') }}
                        </div>
                    </div>
                </div>

                <div v-else-if="tarefa?.status === 'completed' && abaAtiva === 'details'">
                    <div class="text-xs font-bold text-green-600 uppercase mb-4 tracking-wide">
                        {{ $t('crawler.sitemap_updated') }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="border rounded shadow-sm overflow-hidden">
                            <div class="bg-primary-400 text-white text-center py-1 font-bold uppercase text-lg">{{ $t('crawler.pages_card') }}</div>
                            <div class="p-4 flex justify-between items-center bg-white">
                                <div class="text-center">
                                    <span class="text-3xl font-bold text-primary-800">{{ paginasAdicionadas }}</span>
                                    <div class="text-[10px] font-bold text-primary-800 uppercase mt-1">{{ $t('crawler.indexed') }}</div>
                                </div>
                                <div class="text-right text-xs text-gray-600 space-y-1">
                                    <div><span class="font-bold">{{ paginasDescobertas }}</span> {{ $t('crawler.discovered') }}</div>
                                    <div class="text-danger-400"><span class="font-bold">{{ paginasPuladas }}</span> {{ $t('crawler.skipped') }}</div>
                                    <div class="text-green-600"><span class="font-bold">{{ paginasAdicionadas }}</span> {{ $t('crawler.added') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded shadow-sm overflow-hidden">
                            <div class="bg-surface-200 text-white text-center py-1 font-bold uppercase text-lg">{{ $t('crawler.images_card') }}</div>
                            <div class="p-4 text-center bg-white">
                                <span class="text-3xl font-bold text-primary-800">{{ tarefa?.images_count || 0 }}</span>
                                <div class="text-[10px] font-bold text-primary-800 uppercase mt-1">{{ $t('crawler.indexed') }}</div>
                            </div>
                        </div>

                        <div class="border rounded shadow-sm overflow-hidden">
                            <div class="bg-surface-200 text-white text-center py-1 font-bold uppercase text-lg">{{ $t('crawler.videos_card') }}</div>
                            <div class="p-4 text-center bg-white">
                                <span class="text-3xl font-bold text-primary-800">{{ tarefa?.videos_count || 0 }}</span>
                                <div class="text-[10px] font-bold text-primary-800 uppercase mt-1">{{ $t('crawler.indexed') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 mb-4">
                        <button @click="resetarCrawler" :disabled="processandoAcao" class="bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white font-bold text-xs uppercase py-2 px-4 rounded shadow-sm">
                            {{ $t('project.recrawl') }}
                        </button>
                        <button @click="$emit('close')" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold text-xs uppercase py-2 px-4 rounded shadow-sm">
                            {{ $t('modal.close') }}
                        </button>
                    </div>
                </div>

                <div v-else-if="tarefa?.status === 'completed' && abaAtiva === 'files'">
                    <div class="bg-gray-50 border border-gray-200 rounded p-4 mb-6 text-sm text-gray-600">
                        <span class="font-bold">{{ $t('crawler.note_label') }}</span> {{ $t('crawler.note_text') }}
                    </div>

                    <div v-if="arquivosMapeados.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500">
                        {{ $t('project.no_generated_files') }}
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="arquivo in arquivosMapeados" :key="arquivo.name" class="flex items-center justify-between border border-gray-200 rounded p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded bg-gray-100 border border-gray-200 flex items-center justify-center text-xs font-bold text-gray-500">
                                    {{ arquivo.type }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800">{{ arquivo.name }}</div>
                                    <div class="text-xs text-gray-500">{{ arquivo.count }} {{ $t('project.urls_inside') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a :href="arquivo.url" target="_blank" class="text-accent-600 hover:underline text-sm">{{ $t('project.view_action') }}</a>
                                <a :href="arquivo.url" download class="bg-accent-600 hover:bg-accent-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    {{ $t('project.download_action') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="tarefa?.status === 'cancelled'" class="flex flex-col items-center justify-center py-20 text-center">
                    <h3 class="text-xl font-bold text-amber-700 mb-2">{{ $t('crawler.status.cancelled') }}</h3>
                    <p class="text-gray-500 mb-6 max-w-md">{{ tarefa?.message || $t('project.crawling_cancelled') }}</p>
                    <div class="flex gap-3">
                        <button @click="iniciarCrawler" :disabled="processandoAcao" class="bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white font-bold py-3 px-8 rounded shadow-lg">
                            {{ $t('project.recrawl') }}
                        </button>
                        <button @click="$emit('close')" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-3 px-6 rounded shadow-sm">
                            {{ $t('modal.close') }}
                        </button>
                    </div>
                </div>

                <div v-else-if="tarefa?.status === 'failed'" class="flex flex-col items-center justify-center py-20 text-center">
                    <h3 class="text-xl font-bold text-danger-600 mb-2">{{ $t('crawler.status.failed') }}</h3>
                    <p class="text-gray-500 mb-6 max-w-md">{{ tarefa?.message || $t('crawler.error_unknown') }}</p>
                    <div class="flex gap-3">
                        <button @click="iniciarCrawler" :disabled="processandoAcao" class="bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white font-bold py-3 px-8 rounded shadow-lg">
                            {{ $t('project.recrawl') }}
                        </button>
                        <button @click="$emit('close')" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-3 px-6 rounded shadow-sm">
                            {{ $t('modal.close') }}
                        </button>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="mb-6">
                        <svg class="w-16 h-16 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">{{ $t('crawler.ready_title') }}</h3>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto">
                        {{ $t('crawler.ready_desc') }}
                    </p>
                    <button @click="iniciarCrawler" :disabled="processandoAcao" class="bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white font-bold py-3 px-8 rounded shadow-lg flex items-center gap-2 mx-auto">
                        {{ $t('crawler.start_button') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
