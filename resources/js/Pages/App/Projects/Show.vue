<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, reactive, ref, onMounted, onUnmounted, watch } from "vue";
import Swal from 'sweetalert2';
import { trans as t } from "laravel-vue-i18n";
import axios from 'axios';
import UrlDataTable from '@/Components/Project/UrlDataTable.vue';
import PainelSeoProjeto from '@/Components/Project/PainelSeoProjeto.vue';
import PainelSeoBilingue from '@/Components/Project/PainelSeoBilingue.vue';

// Funcionalidade estacionada: mantemos o SEO bilíngue no código para eventual
// reativação futura, mas ela não deve aparecer no painel do usuário neste momento.
const exibirSeoBilingue = false;

const props = defineProps({
    projeto: {
        type: Object,
        required: true,
    },
    ultimo_job: {
        type: Object,
        default: null,
    },
    job_history: {
        type: Array,
        default: () => []
    },
    preview_urls: {
        type: Array,
        default: () => []
    },
    relatorio_seo: {
        type: Object,
        default: () => ({
            disponivel: false,
            fonte: null,
            total_paginas: 0,
            total_links: 0,
            total_links_internos: 0,
            total_links_externos: 0,
            total_links_quebrados: 0,
            paginas_com_links_quebrados: 0,
            paginas_sem_links_entrada: 0,
            paginas_sem_links_saida: 0,
            profundidade_maxima: 0,
            estrutura: {
                diretorios_principais: [],
                distribuicao_profundidade: [],
                paginas_mais_referenciadas: [],
                paginas_sem_links_entrada: [],
                paginas_sem_links_saida: [],
            },
            amostras: {
                links_quebrados: [],
                links_externos: [],
            },
        }),
    },
    seo_bilingue: {
        type: Object,
        default: () => ({
            disponivel: false,
            site_multilingue: false,
            total_paginas: 0,
            idiomas_detectados: [],
            paginas_com_hreflang: 0,
            paginas_sem_hreflang: 0,
            paginas_sem_canonical: 0,
            paginas_sem_autorreferencia: 0,
            paginas_com_x_default: 0,
            amostras: {
                sem_canonical: [],
                sem_hreflang: [],
                sem_autorreferencia: [],
            },
        })
    },
    search_engines: {
        type: Object,
        default: () => ({
            suggested_sitemap_url: '',
            published_sitemap_url: null,
            google_site_property: null,
            bing_site_url: null,
            connections: {
                google: { connected: false, email: null, connected_at: null },
                bing: { connected: false, label: null, connected_at: null },
            },
            recent_submissions: [],
        })
    },
    features: {
        type: Object,
        default: () => ({
            permite_imagens: false,
            permite_videos: false,
            advanced_settings_enabled: false,
            permite_noticias: false,
            permite_mobile: false,
            permite_compactacao: false,
            permite_cache_crawler: false,
            permite_padroes_exclusao: false,
            permite_politicas_crawl: false,
            plan_max_pages: 500,
            allowed_frequencies: ['manual'],
            intervalo_personalizado_min_horas: 1,
            intervalo_personalizado_max_horas: 720,
            intervalo_personalizado_padrao_horas: 24,
            max_depth_limit: 10,
            max_concurrent_requests_limit: 10,
            delay_between_requests_min: 0,
            delay_between_requests_max: 10,
        })
    },
    politicas_crawl: {
        type: Object,
        default: () => ({
            presets: [],
            options: {
                exclude_extensions: [],
                example_prefixes: [],
                example_regex: [],
                example_globs: [],
                defaults: {},
                limits: {},
                query_params_policy_values: [],
            },
        })
    }
});

const abaAtiva = ref("details");
const carregando = ref(false);
const reexecutando = ref(false);
const cancelando = ref(false);

// Estado reativo inicializado com props (cache/ssr)
const tarefa = ref(props.ultimo_job || {});
const historicoJobs = ref([]);
const listaUrls = ref(props.preview_urls || []);
const relatorioSeo = ref(props.relatorio_seo || {});
const seoBilingue = ref(props.seo_bilingue || {});
const salvandoConfiguracoes = ref(false);
const configForm = reactive({
    frequency: props.projeto.frequency || 'manual',
    intervalo_personalizado_horas: props.projeto.intervalo_personalizado_horas ?? props.features.intervalo_personalizado_padrao_horas ?? 24,
    max_pages: Math.min(props.projeto.max_pages ?? props.features.plan_max_pages ?? 500, props.features.plan_max_pages ?? 500),
    max_depth: props.projeto.max_depth ?? 3,
    max_concurrent_requests: props.projeto.max_concurrent_requests ?? 2,
    delay_between_requests: props.projeto.delay_between_requests ?? 1,
    user_agent_custom: props.projeto.user_agent_custom ?? '',
    check_news: !!props.projeto.check_news,
    check_mobile: !!props.projeto.check_mobile,
    compress_output: props.projeto.compress_output ?? true,
    enable_cache: props.projeto.enable_cache ?? true,
    crawl_policy_id: props.projeto.crawl_policy_id ?? '',
});
const textoPadroesExclusao = ref((props.projeto.exclude_patterns ?? []).join('\n'));
const submitForm = reactive({
    published_sitemap_url: props.search_engines?.published_sitemap_url ?? props.search_engines?.suggested_sitemap_url ?? '',
    suggested_sitemap_url: props.search_engines?.suggested_sitemap_url ?? '',
    google_site_property: props.search_engines?.google_site_property ?? '',
    bing_site_url: props.search_engines?.bing_site_url ?? '',
    bing_api_key: '',
});
const googleConnection = ref({ ...(props.search_engines?.connections?.google ?? { connected: false }) });
const bingConnection = ref({ ...(props.search_engines?.connections?.bing ?? { connected: false }) });
const submissionHistory = ref(props.search_engines?.recent_submissions ?? []);
const googleSites = ref([]);
const bingSites = ref([]);
const carregandoGoogleSites = ref(false);
const carregandoBingSites = ref(false);
const salvandoUrlPublica = ref(false);
const salvandoBingKey = ref(false);
const desconectandoGoogle = ref(false);
const removendoBing = ref(false);
const enviandoGoogle = ref(false);
const enviandoBing = ref(false);

// Computado para exibir (usa o estado reativo)
const urlsPreview = computed(() => listaUrls.value);

const contarUrlsPorArtefato = (artifactName) => {
    const normalized = (artifactName || '').toLowerCase();

    if (normalized.includes('image')) {
        return tarefa.value?.images_count ?? 0;
    }

    if (normalized.includes('video')) {
        return tarefa.value?.videos_count ?? 0;
    }

    return tarefa.value?.pages_count ?? 0;
};

const ordenarJobsPorData = (jobs = []) => {
    return [...jobs].sort((jobA, jobB) => {
        const dataA = new Date(jobA?.started_at || jobA?.created_at || jobA?.completed_at || 0).getTime();
        const dataB = new Date(jobB?.started_at || jobB?.created_at || jobB?.completed_at || 0).getTime();
        return dataB - dataA;
    });
};

const normalizarJobHistorico = (job) => ({
    ...job,
    artifacts: Array.isArray(job?.artifacts) ? job.artifacts : [],
});

const substituirHistoricoJobs = (jobs = []) => {
    historicoJobs.value = ordenarJobsPorData(jobs.map(normalizarJobHistorico)).slice(0, 10);
};

const sincronizarJobNoHistorico = (job) => {
    if (!job) return;

    const normalizado = normalizarJobHistorico(job);
    const historicoAtual = [...historicoJobs.value];
    const index = historicoAtual.findIndex((item) => {
        if (normalizado.id && item.id) {
            return item.id === normalizado.id;
        }

        return item.external_job_id && item.external_job_id === normalizado.external_job_id;
    });

    if (index >= 0) {
        historicoAtual[index] = {
            ...historicoAtual[index],
            ...normalizado,
        };
    } else {
        historicoAtual.unshift(normalizado);
    }

    historicoJobs.value = ordenarJobsPorData(historicoAtual).slice(0, 10);
};

substituirHistoricoJobs(props.job_history ?? []);

const arquivosMapeados = computed(() => {
    if (!Array.isArray(tarefa.value?.artifacts)) return [];
    return tarefa.value.artifacts.map((arquivo) => ({
        name: arquivo.name,
        type: arquivo.name?.split('.').pop()?.toUpperCase() || 'FILE',
        count: contarUrlsPorArtefato(arquivo.name),
        url: arquivo.download_url,
    }));

});

const paginasAdicionadas = computed(() => {
    if (!tarefa.value) return 0;
    return tarefa.value.pages_count ?? (tarefa.value.status === 'completed' ? (tarefa.value.urls_found ?? 0) : 0);
});

const paginasPuladas = computed(() => {
    if (!tarefa.value) return 0;
    return tarefa.value.urls_excluded ?? 0;
});

const jobAtivo = computed(() => ['queued', 'running'].includes(tarefa.value?.status));
const acaoEmAndamento = computed(() => reexecutando.value || cancelando.value);
const canEditAdvancedSettings = computed(() => !!props.features.advanced_settings_enabled);
const permiteNoticias = computed(() => !!props.features.permite_noticias);
const permiteMobile = computed(() => !!props.features.permite_mobile);
const permiteCompactacao = computed(() => !!props.features.permite_compactacao);
const permiteCacheCrawler = computed(() => !!props.features.permite_cache_crawler);
const permitePadroesExclusao = computed(() => !!props.features.permite_padroes_exclusao);
const permitePoliticasCrawl = computed(() => !!props.features.permite_politicas_crawl);
const planMaxPages = computed(() => props.features.plan_max_pages ?? 500);
const allowedFrequencyOptions = computed(() => props.features.allowed_frequencies ?? ['manual']);
const intervaloPersonalizadoMinimoHoras = computed(() => props.features.intervalo_personalizado_min_horas ?? 1);
const intervaloPersonalizadoMaximoHoras = computed(() => props.features.intervalo_personalizado_max_horas ?? 720);
const intervaloPersonalizadoPadraoHoras = computed(() => props.features.intervalo_personalizado_padrao_horas ?? 24);
const usaFrequenciaCustomizada = computed(() => configForm.frequency === 'customizado');
const presetsPoliticaCrawl = computed(() => props.politicas_crawl?.presets ?? []);
const exemplosPoliticaCrawl = computed(() => {
    const opcoes = props.politicas_crawl?.options ?? {};

    return [
        ...(opcoes.example_prefixes ?? []),
        ...(opcoes.example_regex ?? []),
        ...(opcoes.example_globs ?? []),
    ].filter(Boolean).slice(0, 6);
});
const limiteEfetivoProjeto = computed(() => Math.min(props.projeto.max_pages ?? planMaxPages.value, planMaxPages.value));
const paginasDescobertasAtuais = computed(() => {
    if (!tarefa.value) return 0;
    if (jobAtivo.value) return tarefa.value.urls_found ?? 0;
    return paginasAdicionadas.value + paginasPuladas.value;
});
const paginasDescobertas = computed(() => paginasDescobertasAtuais.value);
const historicoJobsVisiveis = computed(() => historicoJobs.value);

const statusLabelJob = (status) => {
    const labels = {
        queued: t('project.history_status_queued'),
        running: t('project.history_status_running'),
        completed: t('project.history_status_completed'),
        failed: t('project.history_status_failed'),
        cancelled: t('project.history_status_cancelled'),
    };

    return labels[status] ?? status ?? '-';
};

const statusClasseJob = (status) => {
    const classes = {
        queued: 'bg-amber-50 text-amber-700 border-amber-200',
        running: 'bg-primary-50 text-primary-700 border-primary-200',
        completed: 'bg-green-50 text-green-700 border-green-200',
        failed: 'bg-danger-50 text-danger-700 border-danger-200',
        cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
    };

    return classes[status] ?? 'bg-gray-100 text-gray-600 border-gray-200';
};

const urlsIndexadasDoJob = (job) => {
    if (!job) return 0;
    return job.pages_count ?? (job.status === 'completed' ? (job.urls_found ?? 0) : 0);
};

const urlsDescobertasDoJob = (job) => {
    if (!job) return 0;

    if (['queued', 'running'].includes(job.status)) {
        return job.urls_found ?? 0;
    }

    return urlsIndexadasDoJob(job) + (job.urls_excluded ?? 0);
};

const formatarDataHora = (value) => {
    if (!value) return t('project.history_not_finished');

    return new Date(value).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const proximoRastreamentoDescricao = computed(() => {
    const frequencia = props.projeto.frequency || 'manual';

    if (frequencia === 'manual') {
        return t('project.next_scheduled_run_disabled');
    }

    const proximo = props.projeto.next_scheduled_crawl_at;

    if (!proximo) {
        return t('project.next_scheduled_run_pending');
    }

    if (new Date(proximo).getTime() <= Date.now()) {
        return t('project.next_scheduled_run_due');
    }

    return formatarDataHora(proximo);
});

const formatarDuracaoJob = (job) => {
    if (!job?.started_at) return '-';

    const inicio = new Date(job.started_at).getTime();
    const fim = job.completed_at ? new Date(job.completed_at).getTime() : Date.now();

    if (Number.isNaN(inicio) || Number.isNaN(fim) || fim <= inicio) {
        return '-';
    }

    const segundos = Math.floor((fim - inicio) / 1000);
    const horas = Math.floor(segundos / 3600);
    const minutos = Math.floor((segundos % 3600) / 60);
    const restoSegundos = segundos % 60;

    if (horas > 0) {
        return `${horas}h ${minutos}m`;
    }

    if (minutos > 0) {
        return `${minutos}m ${restoSegundos}s`;
    }

    return `${restoSegundos}s`;
};

const jobMaisRecente = (job, index) => {
    if (!job) return false;
    if (tarefa.value?.external_job_id) {
        return job.external_job_id === tarefa.value.external_job_id;
    }

    return index === 0;
};

const statusColor = computed(() => {
    switch (tarefa.value.status) {
        case 'completed': return 'text-green-600 bg-green-50 border-green-200';
        case 'failed': return 'text-danger-600 bg-danger-50 border-danger-200';
        case 'cancelled': return 'text-amber-700 bg-amber-50 border-amber-200';
        case 'running': return 'text-primary-600 bg-primary-50 border-primary-200';
        default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
});

let pollingInterval = null;

// Busca dados atualizados (Async Lazy Load)
const buscarDetalhesJob = async () => {
    if (!props.projeto.id) return;

    carregando.value = true;
    try {
        const response = await axios.get(route('projects.status', { projeto: props.projeto.id }));
        const data = response.data;

        // Atualiza estado local de forma reativa e completa
        tarefa.value = { ...tarefa.value, ...data };
        sincronizarJobNoHistorico(tarefa.value);
        
        // Garante que métricas específicas sejam preservadas
        if (data.current_url) tarefa.value.current_url = data.current_url;
        if (data.queue_size !== undefined) tarefa.value.queue_size = data.queue_size;
        if (data.current_depth !== undefined) tarefa.value.current_depth = data.current_depth;

        if (data.preview_urls) {
            listaUrls.value = data.preview_urls;
        }

        if (data.next_scheduled_crawl_at !== undefined) {
            props.projeto.next_scheduled_crawl_at = data.next_scheduled_crawl_at;
        }

        if (data.seo_bilingue) {
            seoBilingue.value = data.seo_bilingue;
        }

        if (data.relatorio_seo) {
            relatorioSeo.value = data.relatorio_seo;
        }

        // Se finalizou o crawler durante o polling, para o interval e recarrega página opcionalmente
        if (['completed', 'failed', 'cancelled'].includes(tarefa.value.status)) {
            if (pollingInterval) clearInterval(pollingInterval);
        }
    } catch (error) {
        console.error("Erro ao buscar detalhes atualizados:", error);
    } finally {
        carregando.value = false;
    }
};

const iniciarPolling = () => {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(() => {
        buscarDetalhesJob();
    }, 5000); // Poll a cada 5s
};

onMounted(() => {
    // Se não tiver urls de preview ou se o status for inconclusivo, busca atualização
    if (listaUrls.value.length === 0 || ['queued', 'running'].includes(tarefa.value.status)) {
        buscarDetalhesJob();
    }
    
    // Inicia polling se necessário
    if (['queued', 'running'].includes(tarefa.value.status)) {
        iniciarPolling();
    }
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

const normalizarPadroesExclusao = () => {
    return textoPadroesExclusao.value
        .split(/\r?\n/)
        .map((item) => item.trim())
        .filter(Boolean);
};

const resetConfigForm = () => {
    configForm.frequency = props.projeto.frequency || 'manual';
    configForm.intervalo_personalizado_horas = props.projeto.intervalo_personalizado_horas ?? intervaloPersonalizadoPadraoHoras.value;
    configForm.max_pages = Math.min(props.projeto.max_pages ?? planMaxPages.value, planMaxPages.value);
    configForm.max_depth = props.projeto.max_depth ?? 3;
    configForm.max_concurrent_requests = props.projeto.max_concurrent_requests ?? 2;
    configForm.delay_between_requests = props.projeto.delay_between_requests ?? 1;
    configForm.user_agent_custom = props.projeto.user_agent_custom ?? '';
    configForm.check_news = !!props.projeto.check_news;
    configForm.check_mobile = !!props.projeto.check_mobile;
    configForm.compress_output = props.projeto.compress_output ?? true;
    configForm.enable_cache = props.projeto.enable_cache ?? true;
    configForm.crawl_policy_id = props.projeto.crawl_policy_id ?? '';
    textoPadroesExclusao.value = (props.projeto.exclude_patterns ?? []).join('\n');
};

watch(() => props.projeto, () => {
    resetConfigForm();
}, { deep: true });

watch(() => props.job_history, (jobs) => {
    substituirHistoricoJobs(jobs ?? []);
}, { deep: true });

watch(() => props.seo_bilingue, (value) => {
    seoBilingue.value = value ?? {};
}, { deep: true });

watch(() => props.relatorio_seo, (value) => {
    relatorioSeo.value = value ?? {};
}, { deep: true });

watch(() => configForm.frequency, (frequencia) => {
    if (frequencia === 'customizado' && !configForm.intervalo_personalizado_horas) {
        configForm.intervalo_personalizado_horas = intervaloPersonalizadoPadraoHoras.value;
    }
});

watch(abaAtiva, (value) => {
    if (!exibirSeoBilingue && value === 'seo_bilingue') {
        abaAtiva.value = 'details';
    }
});

watch(() => props.search_engines, (value) => {
    googleConnection.value = { ...(value?.connections?.google ?? { connected: false }) };
    bingConnection.value = { ...(value?.connections?.bing ?? { connected: false }) };
    submitForm.published_sitemap_url = value?.published_sitemap_url ?? value?.suggested_sitemap_url ?? '';
    submitForm.suggested_sitemap_url = value?.suggested_sitemap_url ?? '';
    submitForm.google_site_property = value?.google_site_property ?? '';
    submitForm.bing_site_url = value?.bing_site_url ?? '';
    sincronizarSubmissoes(value?.recent_submissions ?? []);
}, { deep: true });

watch(abaAtiva, (novaAba) => {
    if (novaAba !== 'submit') {
        return;
    }

    if (googleConnection.value?.connected && googleSites.value.length === 0) {
        carregarGoogleSites();
    }

    if (bingConnection.value?.connected && bingSites.value.length === 0) {
        carregarBingSites();
    }
});

const frequencyLabel = (value) => {
    const map = {
        manual: t('freq.manual'),
        customizado: t('freq.custom'),
        diario: t('freq.daily'),
        semanal: t('freq.weekly'),
        quinzenal: t('freq.biweekly'),
        mensal: t('freq.monthly'),
        anual: t('freq.yearly'),
    };

    return map[value] ?? value;
};

const submissionStatusLabel = (status) => {
    const labels = {
        submitted: t('project.submit_status_submitted'),
        failed: t('project.submit_status_failed'),
    };

    return labels[status] ?? status ?? '-';
};

const submissionStatusClass = (status) => {
    if (status === 'submitted') {
        return 'bg-green-50 text-green-700 border-green-200';
    }

    if (status === 'failed') {
        return 'bg-danger-50 text-danger-700 border-danger-200';
    }

    return 'bg-gray-100 text-gray-600 border-gray-200';
};

const sincronizarSubmissoes = (submissions = []) => {
    submissionHistory.value = [...submissions].sort((itemA, itemB) => {
        const dataA = new Date(itemA?.submitted_at || 0).getTime();
        const dataB = new Date(itemB?.submitted_at || 0).getTime();
        return dataB - dataA;
    });
};

const extrairMensagemErro = (error, fallbackKey = 'project.submit_error') => {
    const fieldErrors = error?.response?.data?.errors;

    if (fieldErrors && typeof fieldErrors === 'object') {
        const firstError = Object.values(fieldErrors)[0];
        if (Array.isArray(firstError)) {
            return firstError[0];
        }

        if (typeof firstError === 'string') {
            return firstError;
        }
    }

    return error?.response?.data?.message ?? t(fallbackKey);
};

const carregarGoogleSites = async () => {
    if (!googleConnection.value?.connected || carregandoGoogleSites.value) {
        return;
    }

    carregandoGoogleSites.value = true;

    try {
        const response = await axios.get(route('projects.search-engines.google.sites', { projeto: props.projeto.id }));
        googleSites.value = response.data?.sites ?? [];

        if (!submitForm.google_site_property) {
            submitForm.google_site_property = response.data?.recommended ?? '';
        }
    } catch (error) {
        googleSites.value = [];
        await Swal.fire({
            title: t('common.error'),
            text: extrairMensagemErro(error, 'project.submit_sites_error'),
            icon: 'error',
        });
    } finally {
        carregandoGoogleSites.value = false;
    }
};

const carregarBingSites = async () => {
    if (!bingConnection.value?.connected || carregandoBingSites.value) {
        return;
    }

    carregandoBingSites.value = true;

    try {
        const response = await axios.get(route('projects.search-engines.bing.sites', { projeto: props.projeto.id }));
        bingSites.value = response.data?.sites ?? [];

        if (!submitForm.bing_site_url) {
            submitForm.bing_site_url = response.data?.recommended ?? '';
        }
    } catch (error) {
        bingSites.value = [];
        await Swal.fire({
            title: t('common.error'),
            text: extrairMensagemErro(error, 'project.submit_sites_error'),
            icon: 'error',
        });
    } finally {
        carregandoBingSites.value = false;
    }
};

const salvarUrlPublica = () => {
    if (salvandoUrlPublica.value) return;

    salvandoUrlPublica.value = true;

    router.patch(route('projects.update', { projeto: props.projeto.id }), {
        published_sitemap_url: submitForm.published_sitemap_url || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            props.projeto.published_sitemap_url = submitForm.published_sitemap_url || null;
            Swal.fire({
                title: t('project.submit_url_saved'),
                icon: 'success',
                timer: 1800,
                showConfirmButton: false,
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];

            Swal.fire({
                title: t('common.error'),
                text: Array.isArray(firstError) ? firstError[0] : (firstError || t('project.update_error')),
                icon: 'error',
            });
        },
        onFinish: () => {
            salvandoUrlPublica.value = false;
        }
    });
};

const conectarGoogleSearchConsole = () => {
    window.location.href = route('search-engines.google.connect', { project: props.projeto.id });
};

const desconectarGoogleSearchConsole = async () => {
    if (desconectandoGoogle.value) return;

    desconectandoGoogle.value = true;

    try {
        await axios.delete(route('search-engines.google.disconnect'));
        googleConnection.value = { connected: false, email: null, connected_at: null };
        googleSites.value = [];
        submitForm.google_site_property = '';
        await Swal.fire({
            title: t('project.submit_google_disconnected'),
            icon: 'success',
            timer: 1600,
            showConfirmButton: false,
        });
    } catch (error) {
        await Swal.fire({
            title: t('common.error'),
            text: extrairMensagemErro(error, 'project.submit_disconnect_error'),
            icon: 'error',
        });
    } finally {
        desconectandoGoogle.value = false;
    }
};

const salvarBingWebmasterKey = async () => {
    if (salvandoBingKey.value || !submitForm.bing_api_key) return;

    salvandoBingKey.value = true;

    try {
        const response = await axios.post(route('search-engines.bing.store'), {
            api_key: submitForm.bing_api_key,
        });

        bingConnection.value = response.data?.connection ?? { connected: true };
        submitForm.bing_api_key = '';
        await carregarBingSites();

        await Swal.fire({
            title: t('project.submit_bing_connected'),
            text: t('project.submit_bing_connected_desc', { count: response.data?.sites_count ?? 0 }),
            icon: 'success',
        });
    } catch (error) {
        await Swal.fire({
            title: t('common.error'),
            text: extrairMensagemErro(error, 'project.submit_connect_error'),
            icon: 'error',
        });
    } finally {
        salvandoBingKey.value = false;
    }
};

const removerBingWebmasterKey = async () => {
    if (removendoBing.value) return;

    removendoBing.value = true;

    try {
        await axios.delete(route('search-engines.bing.destroy'));
        bingConnection.value = { connected: false, label: null, connected_at: null };
        bingSites.value = [];
        submitForm.bing_site_url = '';
        await Swal.fire({
            title: t('project.submit_bing_disconnected'),
            icon: 'success',
            timer: 1600,
            showConfirmButton: false,
        });
    } catch (error) {
        await Swal.fire({
            title: t('common.error'),
            text: extrairMensagemErro(error, 'project.submit_disconnect_error'),
            icon: 'error',
        });
    } finally {
        removendoBing.value = false;
    }
};

const enviarSitemapGoogle = async () => {
    if (enviandoGoogle.value) return;

    enviandoGoogle.value = true;

    try {
        const response = await axios.post(route('projects.search-engines.google.submit', { projeto: props.projeto.id }), {
            site_property: submitForm.google_site_property,
            published_sitemap_url: submitForm.published_sitemap_url,
        });

        submitForm.published_sitemap_url = response.data?.published_sitemap_url ?? submitForm.published_sitemap_url;
        submitForm.google_site_property = response.data?.google_site_property ?? submitForm.google_site_property;
        props.projeto.published_sitemap_url = submitForm.published_sitemap_url;
        sincronizarSubmissoes(response.data?.recent_submissions ?? []);

        await Swal.fire({
            title: t('project.submit_google_success'),
            text: t('project.submit_success_desc'),
            icon: 'success',
        });
    } catch (error) {
        sincronizarSubmissoes(error.response?.data?.recent_submissions ?? submissionHistory.value);

        await Swal.fire({
            title: t('common.error'),
            text: extrairMensagemErro(error, 'project.submit_error'),
            icon: 'error',
        });
    } finally {
        enviandoGoogle.value = false;
    }
};

const enviarSitemapBing = async () => {
    if (enviandoBing.value) return;

    enviandoBing.value = true;

    try {
        const response = await axios.post(route('projects.search-engines.bing.submit', { projeto: props.projeto.id }), {
            site_url: submitForm.bing_site_url,
            published_sitemap_url: submitForm.published_sitemap_url,
        });

        submitForm.published_sitemap_url = response.data?.published_sitemap_url ?? submitForm.published_sitemap_url;
        submitForm.bing_site_url = response.data?.bing_site_url ?? submitForm.bing_site_url;
        props.projeto.published_sitemap_url = submitForm.published_sitemap_url;
        sincronizarSubmissoes(response.data?.recent_submissions ?? []);

        await Swal.fire({
            title: t('project.submit_bing_success'),
            text: t('project.submit_success_desc'),
            icon: 'success',
        });
    } catch (error) {
        sincronizarSubmissoes(error.response?.data?.recent_submissions ?? submissionHistory.value);

        await Swal.fire({
            title: t('common.error'),
            text: extrairMensagemErro(error, 'project.submit_error'),
            icon: 'error',
        });
    } finally {
        enviandoBing.value = false;
    }
};

const confirmarExclusao = () => {
    Swal.fire({
        title: t('project.delete_confirm_title'),
        text: t('project.delete_confirm_text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: t('project.delete_confirm_btn'),
        cancelButtonText: t('project.delete_cancel_btn')
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('projects.destroy', { projeto: props.projeto.id }));
        }
    })
};

const iniciarNovoCrawler = async () => {
    if (acaoEmAndamento.value) return;

    reexecutando.value = true;

    try {
        const response = await axios.post(route('projects.crawl', { projeto: props.projeto.id }));

        tarefa.value = {
            ...tarefa.value,
            status: response.data.status ?? 'queued',
            external_job_id: response.data.job_id ?? null,
            progress: 0,
            pages_count: 0,
            urls_found: 0,
            urls_crawled: 0,
            urls_excluded: 0,
            message: response.data.message ?? null,
            started_at: new Date().toISOString(),
            completed_at: null,
            artifacts: [],
        };
        sincronizarJobNoHistorico(tarefa.value);
        listaUrls.value = [];
        iniciarPolling();
    } catch (error) {
        if (error.response?.status === 409) {
            await buscarDetalhesJob();

            await Swal.fire({
                title: t('crawler.update_in_progress'),
                text: error.response?.data?.message ?? t('crawler.in_progress_desc'),
                icon: 'info',
            });
        } else {
            await Swal.fire({
                title: t('common.error'),
                text: error.response?.data?.message ?? t('crawler.error_generic'),
                icon: 'error',
            });
        }
    } finally {
        reexecutando.value = false;
    }
};

const cancelarCrawler = async () => {
    if (acaoEmAndamento.value || !jobAtivo.value) return;

    const result = await Swal.fire({
        title: t('crawler.cancel_confirm_title'),
        text: t('crawler.cancel_confirm_text'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: t('crawler.cancel'),
        cancelButtonText: t('project.delete_cancel_btn'),
    });

    if (!result.isConfirmed) {
        return;
    }

    cancelando.value = true;

    try {
        const response = await axios.post(route('projects.crawl.cancel', { projeto: props.projeto.id }));

        tarefa.value = {
            ...tarefa.value,
            status: response.data.status ?? 'cancelled',
            message: response.data.message ?? null,
            completed_at: response.data.completed_at ?? null,
        };
        sincronizarJobNoHistorico(tarefa.value);

        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    } catch (error) {
        await buscarDetalhesJob();

        await Swal.fire({
            title: t('common.error'),
            text: error.response?.data?.message ?? t('crawler.error_generic'),
            icon: 'error',
        });
    } finally {
        cancelando.value = false;
    }
};


const downloadUrl = computed(() => {
    if (!tarefa.value || !tarefa.value.artifacts) return null;
    const artifact = tarefa.value.artifacts.find(a => a.name && a.name.endsWith('.xml')) || 
                     tarefa.value.artifacts.find(a => a.name && a.name.endsWith('.txt'));
    return artifact ? artifact.download_url : null;
});

const salvarConfiguracoes = () => {
    if (salvandoConfiguracoes.value) return;

    salvandoConfiguracoes.value = true;

    router.patch(route('projects.update', { projeto: props.projeto.id }), {
        frequency: configForm.frequency,
        intervalo_personalizado_horas: usaFrequenciaCustomizada.value ? configForm.intervalo_personalizado_horas : null,
        max_pages: configForm.max_pages,
        max_depth: configForm.max_depth,
        max_concurrent_requests: configForm.max_concurrent_requests,
        delay_between_requests: configForm.delay_between_requests,
        user_agent_custom: configForm.user_agent_custom,
        check_news: configForm.check_news,
        check_mobile: configForm.check_mobile,
        exclude_patterns: normalizarPadroesExclusao(),
        crawl_policy_id: configForm.crawl_policy_id || null,
        compress_output: configForm.compress_output,
        enable_cache: configForm.enable_cache,
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            const projetoAtualizado = page?.props?.projeto ?? {};

            props.projeto.frequency = projetoAtualizado.frequency || configForm.frequency;
            props.projeto.intervalo_personalizado_horas = projetoAtualizado.intervalo_personalizado_horas ?? (usaFrequenciaCustomizada.value ? configForm.intervalo_personalizado_horas : null);
            props.projeto.max_pages = projetoAtualizado.max_pages ?? configForm.max_pages;
            props.projeto.max_depth = projetoAtualizado.max_depth ?? configForm.max_depth;
            props.projeto.max_concurrent_requests = projetoAtualizado.max_concurrent_requests ?? configForm.max_concurrent_requests;
            props.projeto.delay_between_requests = projetoAtualizado.delay_between_requests ?? configForm.delay_between_requests;
            props.projeto.user_agent_custom = projetoAtualizado.user_agent_custom ?? configForm.user_agent_custom;
            props.projeto.check_news = projetoAtualizado.check_news ?? configForm.check_news;
            props.projeto.check_mobile = projetoAtualizado.check_mobile ?? configForm.check_mobile;
            props.projeto.exclude_patterns = projetoAtualizado.exclude_patterns ?? normalizarPadroesExclusao();
            props.projeto.crawl_policy_id = projetoAtualizado.crawl_policy_id ?? (configForm.crawl_policy_id || null);
            props.projeto.compress_output = projetoAtualizado.compress_output ?? configForm.compress_output;
            props.projeto.enable_cache = projetoAtualizado.enable_cache ?? configForm.enable_cache;
            props.projeto.next_scheduled_crawl_at = projetoAtualizado.next_scheduled_crawl_at ?? null;

            configForm.frequency = props.projeto.frequency;
            configForm.intervalo_personalizado_horas = props.projeto.intervalo_personalizado_horas ?? intervaloPersonalizadoPadraoHoras.value;

            Swal.fire({
                title: t('project.settings_saved'),
                icon: 'success',
                timer: 1800,
                showConfirmButton: false,
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];

            Swal.fire({
                title: t('common.error'),
                text: Array.isArray(firstError) ? firstError[0] : (firstError || t('project.update_error')),
                icon: 'error',
            });

            resetConfigForm();
        },
        onFinish: () => {
            salvandoConfiguracoes.value = false;
        }
    });
};

const toggleFeature = (feature) => {
    const newValue = !props.projeto[feature];
    
    // Feedback visual imediato (otimista)
    props.projeto[feature] = newValue;

    router.patch(route('projects.update', { projeto: props.projeto.id }), {
        [feature]: newValue
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Notificação de sucesso silenciada ou toast
        },
        onError: () => {
            // Reverte em caso de erro
            props.projeto[feature] = !newValue;
            Swal.fire({
                title: t('common.error'),
                text: t('project.update_error'),
                icon: 'error'
            });
        }
    });
};
</script>

<template>

    <Head :title="projeto.name || $t('project.details')" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10">
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
                                    {{ projeto.name || projeto.url }}
                                </h1>
                                <a :href="projeto.url" target="_blank"
                                    class="text-sm text-primary-500 hover:underline flex items-center gap-1">
                                    {{ projeto.url }}
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
                            <DangerButton @click="confirmarExclusao" :disabled="acaoEmAndamento">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                                {{ $t('project.delete') }}
                            </DangerButton>
                            
                            <DangerButton v-if="jobAtivo" type="button" @click="cancelarCrawler" :disabled="acaoEmAndamento">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 9v6m4-6v6m5-3a9 9 0 11-18 0 9 9 0 0118 0z">
                                    </path>
                                </svg>
                                {{ cancelando ? $t('crawler.cancelling') : $t('crawler.cancel') }}
                            </DangerButton>

                            <PrimaryButton v-else type="button" @click="iniciarNovoCrawler" :disabled="acaoEmAndamento">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                    </path>
                                </svg>
                                {{ reexecutando ? $t('crawler.starting') : $t('project.recrawl') }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

                <!-- Alerta se falho -->
                <div v-if="tarefa.status === 'failed'" class="mb-6 bg-danger-50 border-l-4 border-danger-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-danger-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-danger-700">
                                {{ $t('project.crawling_failed') }}. <span v-if="tarefa.message">{{ tarefa.message
                                    }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="tarefa.status === 'cancelled'" class="mb-6 bg-amber-50 border-l-4 border-amber-500 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-9-4a1 1 0 112 0v4a1 1 0 11-2 0V6zm1 8a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 14z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-amber-800">
                                {{ $t('project.crawling_cancelled') }}. <span v-if="tarefa.message">{{ tarefa.message }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas (Cards) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Pages Card -->
                    <div class="bg-white border rounded-lg shadow-sm overflow-hidden">
                        <div class="bg-primary-400 text-white text-center py-2 font-bold uppercase tracking-wider">{{
                            $t('project.pages_discovered') }}</div>
                        <div class="p-6 flex justify-between items-center">
                            <div>
                                <span class="text-4xl font-bold text-primary-800">{{ paginasAdicionadas }}</span>
                                <div class="text-xs font-bold text-gray-400 uppercase mt-1">{{
                                    $t('project.total_indexed') }}</div>
                            </div>
                            <div class="text-right text-xs text-gray-600 space-y-1">
                                <div><span class="font-bold">{{ paginasDescobertas }}</span> {{
                                    $t('project.stat_discovered') }}</div>
                                <div class="text-danger-400"><span class="font-bold">{{ paginasPuladas }}</span> {{
                                    $t('crawler.skipped') }}</div>
                                <div class="text-green-600"><span class="font-bold">{{ paginasAdicionadas }}</span>
                                    {{ $t('project.new_added') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Images Card -->
                    <div class="bg-white border rounded-lg shadow-sm overflow-hidden opacity-75">
                        <div class="bg-surface-200 text-gray-600 text-center py-2 font-bold uppercase tracking-wider">{{
                            $t('project.images') }}</div>
                        <div class="p-6 text-center">
                            <div v-if="features.permite_imagens">
                                <span class="text-4xl font-bold text-primary-800">{{ tarefa.images_count || 0 }}</span>
                                <div class="text-xs font-bold text-gray-400 uppercase mt-1">{{ $t('project.stat_indexed') }}</div>
                                
                                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-gray-500 uppercase">{{ $t('project.track_images') }}</span>
                                    <button @click="toggleFeature('check_images')" :class="['relative inline-flex h-4 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none', projeto.check_images ? 'bg-primary-600' : 'bg-gray-200']">
                                        <span :class="['translate-x-0 pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', projeto.check_images ? 'translate-x-4' : 'translate-x-0']"></span>
                                    </button>
                                </div>
                            </div>
                            <div v-else>
                                <span class="text-4xl font-bold text-gray-400">0</span>
                                <div class="text-xs font-bold text-gray-400 uppercase mt-1">{{ $t('project.stat_indexed') }}
                                </div>
                                <span
                                    class="mt-2 inline-block bg-green-500 text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold">{{
                                    $t('project.pro_feature') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Videos Card -->
                    <div class="bg-white border rounded-lg shadow-sm overflow-hidden opacity-75">
                        <div class="bg-surface-200 text-gray-600 text-center py-2 font-bold uppercase tracking-wider">{{
                            $t('project.videos') }}</div>
                        <div class="p-6 text-center">
                            <div v-if="features.permite_videos">
                                <span class="text-4xl font-bold text-primary-800">{{ tarefa.videos_count || 0 }}</span>
                                <div class="text-xs font-bold text-gray-400 uppercase mt-1">{{ $t('project.stat_indexed') }}</div>

                                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-gray-500 uppercase">{{ $t('project.track_videos') }}</span>
                                    <button @click="toggleFeature('check_videos')" :class="['relative inline-flex h-4 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none', projeto.check_videos ? 'bg-primary-600' : 'bg-gray-200']">
                                        <span :class="['translate-x-0 pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', projeto.check_videos ? 'translate-x-4' : 'translate-x-0']"></span>
                                    </button>
                                </div>
                            </div>
                            <div v-else>
                                <span class="text-4xl font-bold text-gray-400">0</span>
                                <div class="text-xs font-bold text-gray-400 uppercase mt-1">{{ $t('project.stat_indexed') }}
                                </div>
                                <span
                                    class="mt-2 inline-block bg-green-500 text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold">{{
                                    $t('project.pro_feature') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conteúdo Principal com Abas -->
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden min-h-[500px]">
                    <!-- Headers das Abas -->
                    <div class="flex border-b border-gray-200 bg-gray-50 px-6 pt-4">
                        <button @click="abaAtiva = 'details'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all mr-1 translate-y-[1px]', abaAtiva === 'details' ? 'bg-white text-accent-600 border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">⁕</span> {{ $t('project.sitemap_overview') }}
                        </button>
                        <button @click="abaAtiva = 'files'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all mr-1 translate-y-[1px]', abaAtiva === 'files' ? 'bg-white text-accent-600 border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">☁</span> {{ $t('project.download_files') }}
                        </button>
                        <button @click="abaAtiva = 'seo'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all mr-1 translate-y-[1px]', abaAtiva === 'seo' ? 'bg-white text-accent-600 border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">SEO</span> {{ $t('project.seo_report_tab') }}
                        </button>
                        <button v-if="exibirSeoBilingue" @click="abaAtiva = 'seo_bilingue'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all mr-1 translate-y-[1px]', abaAtiva === 'seo_bilingue' ? 'bg-white text-accent-600 border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">SEO</span> {{ $t('project.seo_bilingual_tab') }}
                        </button>
                        <button @click="abaAtiva = 'submit'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all mr-1 translate-y-[1px]', abaAtiva === 'submit' ? 'bg-white text-accent-600 border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">+</span> {{ $t('project.submit_tab') }}
                        </button>
                        <button @click="abaAtiva = 'history'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all mr-1 translate-y-[1px]', abaAtiva === 'history' ? 'bg-white text-accent-600 border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">◷</span> {{ $t('project.history_tab') }}
                        </button>
                        <button @click="abaAtiva = 'settings'"
                            :class="['px-6 py-3 text-sm font-bold uppercase border-t border-l border-r rounded-t-lg transition-all translate-y-[1px]', abaAtiva === 'settings' ? 'bg-white text-accent-600 border-gray-200 border-b-white shadow-sm' : 'bg-gray-100 text-gray-500 border-transparent hover:bg-white/50']">
                            <span class="mr-2">⚙</span> {{ $t('project.settings_tab') }}
                        </button>
                    </div>

                    <!-- Conteúdo da Aba -->
                    <div class="p-8">

                        <!-- ABA DETAILS -->
                        <div v-if="abaAtiva === 'details'">
                            
                            <!-- Bloco "AGUARDE" (Animado/Polling) -->
                            <div v-if="['queued', 'running'].includes(tarefa.status)" class="text-center py-12 px-4 border border-primary-100 bg-primary-50/20 rounded-lg">
                                <h2 class="text-primary-500 text-xl font-bold uppercase tracking-wider mb-4">{{ $t('crawler.please_wait') }}</h2>
                                <p class="text-gray-700 font-medium mb-1" style="font-size: 15px;">
                                    {{ appName }} {{ $t('crawler.please_wait_msg1') }} <strong>{{ projeto.name || projeto.url }}</strong>,
                                </p>
                                <p class="text-gray-600 mb-8" style="font-size: 15px;">
                                    {{ $t('crawler.please_wait_msg2') }}<br>
                                    {{ $t('crawler.please_wait_msg3') }}
                                </p>
                                
                                <div class="max-w-xl mx-auto border-t border-b border-gray-200 py-6">
                                    <div class="flex items-center justify-center gap-3 mb-4">
                                        <span class="text-gray-700">{{ $t('crawler.update_in_progress') }}</span>
                                        <button @click="cancelarCrawler" :disabled="acaoEmAndamento" class="bg-warning-500 hover:bg-warning-600 disabled:opacity-60 text-white text-xs font-bold uppercase px-3 py-1 rounded flex items-center gap-1 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ cancelando ? $t('crawler.cancelling') : $t('crawler.cancel') }}
                                        </button>
                                    </div>
                                    
                                    <div class="text-sm text-gray-700 leading-relaxed mb-4">
                                        <p>{{ $t('crawler.pages_processed') }}: <strong>{{ tarefa.urls_crawled || 0 }}</strong></p>
                                        <p>{{ $t('crawler.urls_discovered') }}: <strong>{{ paginasDescobertasAtuais }}</strong> • {{ $t('crawler.project_limit') }}: <strong>{{ limiteEfetivoProjeto }}</strong></p>
                                        <p v-if="paginasDescobertasAtuais > limiteEfetivoProjeto" class="text-primary-600 font-medium">{{ $t('crawler.limit_notice', { count: limiteEfetivoProjeto }) }}</p>
                                        <p v-if="tarefa.current_url" class="truncate max-w-lg mx-auto mb-1">
                                            {{ $t('crawler.current_page') }}: <span class="text-xs text-primary-600 font-mono">{{ tarefa.current_url }}</span>
                                        </p>
                                        <div class="flex justify-center gap-4 text-xs font-bold text-gray-500 uppercase">
                                            <span>{{ $t('crawler.queued') }}: <span class="text-gray-900">{{ tarefa.queue_size || 0 }}</span></span>
                                            <span>•</span>
                                            <span>{{ $t('crawler.depth_level') }}: <span class="text-gray-900">{{ tarefa.current_depth || 0 }}</span></span>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bar Container -->
                                    <div class="w-full h-3 bg-primary-100 rounded-full overflow-hidden flex">
                                        <div class="h-full bg-primary-500 transition-all duration-500 rounded-l-full" :style="{ width: Math.max(10, Math.min(100, Math.floor(tarefa.progress || 0))) + '%' }"></div>
                                        <div class="h-full bg-warning-500 transition-all duration-500 rounded-r-full flex-grow animate-pulse opacity-50"></div>
                                    </div>
                                </div>

                                <!-- Botão SAIR -->
                                <div class="mt-6">
                                    <Link :href="route('dashboard')" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 font-bold text-sm uppercase tracking-wider px-5 py-2 rounded transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        {{ $t('crawler.exit') }}
                                    </Link>
                                </div>
                            </div>
                            
                            <!-- Boco Normal -->
                            <div v-else>
                                <div class="flex justify-between items-center mb-6">
                                    <h3 class="text-lg font-bold text-gray-700">{{ $t('project.recent_urls') }}</h3>
                                    <div class="text-sm text-gray-500">
                                         <a v-if="downloadUrl" :href="downloadUrl" target="_blank" class="text-primary-500 hover:underline">
                                            {{ $t('project.download_full_list') }}
                                        </a>
                                    </div>
                                </div>
    
                                <!-- Tabela Paginada via API -->
                                <UrlDataTable :projeto-id="projeto.id" />
                            </div>
                        </div>
                        

                        <div v-else-if="abaAtiva === 'seo'">
                            <PainelSeoProjeto :relatorio-seo="relatorioSeo" />
                        </div>

                        <div v-else-if="exibirSeoBilingue && abaAtiva === 'seo_bilingue'">
                            <PainelSeoBilingue :seo-bilingue="seoBilingue" />
                        </div>

                        <!-- ABA FILES -->
                        <div v-else-if="abaAtiva === 'files'">
                            <div class="bg-primary-50 border border-primary-100 rounded-lg p-4 mb-8 flex items-start gap-3">
                                <svg class="w-6 h-6 text-primary-500 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm text-primary-800">
                                    <p class="font-bold mb-1">{{ $t('project.integration_instructions') }}</p>
                                    <p>{{ $t('project.integration_text') }}</p>
                                </div>
                            </div>

                            <h3 class="text-lg font-bold text-gray-700 mb-4">{{ $t('project.available_downloads') }}
                            </h3>

                            <div v-if="arquivosMapeados.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500">
                                {{ $t('project.no_generated_files') }}
                            </div>

                            <div v-else class="grid grid-cols-1 gap-4">
                                <div v-for="arq in arquivosMapeados" :key="arq.name"
                                    class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-primary-300 hover:shadow-md transition bg-white group">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center text-gray-500 font-bold uppercase text-xs border border-gray-200">
                                            {{ arq.type }}
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-800 group-hover:text-primary-600 transition">{{
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
                                            class="bg-accent-600 hover:bg-accent-700 text-white font-bold py-2 px-4 rounded text-sm shadow flex items-center gap-2">
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

                        <div v-else-if="abaAtiva === 'submit'" class="space-y-8">
                            <div class="bg-primary-50 border border-primary-100 rounded-lg p-5">
                                <h3 class="text-lg font-bold text-primary-900 mb-2">{{ $t('project.submit_title') }}</h3>
                                <p class="text-sm text-primary-800">{{ $t('project.submit_intro') }}</p>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <div>
                                        <h4 class="text-base font-bold text-gray-800">{{ $t('project.submit_public_url_title') }}</h4>
                                        <p class="text-sm text-gray-500">{{ $t('project.submit_public_url_help') }}</p>
                                    </div>
                                    <button type="button"
                                        class="text-xs font-bold uppercase tracking-wide text-primary-600 hover:text-primary-700"
                                        @click="submitForm.published_sitemap_url = submitForm.suggested_sitemap_url">
                                        {{ $t('project.submit_use_suggestion') }}
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <input
                                        v-model="submitForm.published_sitemap_url"
                                        type="url"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                        :placeholder="submitForm.suggested_sitemap_url || 'https://example.com/sitemap.xml'"
                                    />

                                    <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                                        <div class="text-xs text-gray-500">
                                            {{ $t('project.submit_public_url_note') }}
                                        </div>
                                        <PrimaryButton type="button" @click="salvarUrlPublica" :disabled="salvandoUrlPublica">
                                            {{ salvandoUrlPublica ? $t('project.submit_url_saving') : $t('project.submit_url_save') }}
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h4 class="text-base font-bold text-gray-800">Google Search Console</h4>
                                            <p class="text-sm text-gray-500">{{ $t('project.submit_google_help') }}</p>
                                        </div>
                                        <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wide', googleConnection.connected ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-500']">
                                            {{ googleConnection.connected ? $t('project.submit_connected') : $t('project.submit_not_connected') }}
                                        </span>
                                    </div>

                                    <div v-if="googleConnection.connected" class="space-y-4">
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                                            <strong class="text-gray-800">{{ googleConnection.email || 'Google' }}</strong>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $t('project.submit_google_property') }}</label>
                                                <button type="button" class="text-xs font-bold uppercase tracking-wide text-primary-600 hover:text-primary-700" @click="carregarGoogleSites">
                                                    {{ carregandoGoogleSites ? $t('project.submit_loading_sites') : $t('project.submit_refresh_sites') }}
                                                </button>
                                            </div>
                                            <select
                                                v-model="submitForm.google_site_property"
                                                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >
                                                <option value="">{{ $t('project.submit_select_google_property') }}</option>
                                                <option v-for="site in googleSites" :key="site.site_url" :value="site.site_url">
                                                    {{ site.site_url }} ({{ site.permission_level }})
                                                </option>
                                            </select>
                                        </div>

                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <PrimaryButton type="button" @click="enviarSitemapGoogle" :disabled="enviandoGoogle || !submitForm.google_site_property">
                                                {{ enviandoGoogle ? $t('project.submit_sending') : $t('project.submit_google_action') }}
                                            </PrimaryButton>
                                            <button type="button" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="desconectarGoogleSearchConsole" :disabled="desconectandoGoogle">
                                                {{ desconectandoGoogle ? $t('project.submit_disconnecting') : $t('project.submit_google_disconnect') }}
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else class="space-y-4">
                                        <p class="text-sm text-gray-500">{{ $t('project.submit_google_connect_desc') }}</p>
                                        <PrimaryButton type="button" @click="conectarGoogleSearchConsole">
                                            {{ $t('project.submit_google_connect') }}
                                        </PrimaryButton>
                                    </div>
                                </div>

                                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h4 class="text-base font-bold text-gray-800">Bing Webmaster Tools</h4>
                                            <p class="text-sm text-gray-500">{{ $t('project.submit_bing_help') }}</p>
                                        </div>
                                        <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wide', bingConnection.connected ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-500']">
                                            {{ bingConnection.connected ? $t('project.submit_connected') : $t('project.submit_not_connected') }}
                                        </span>
                                    </div>

                                    <div v-if="bingConnection.connected" class="space-y-4">
                                        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                                            <strong class="text-gray-800">{{ bingConnection.label }}</strong>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $t('project.submit_bing_site') }}</label>
                                                <button type="button" class="text-xs font-bold uppercase tracking-wide text-primary-600 hover:text-primary-700" @click="carregarBingSites">
                                                    {{ carregandoBingSites ? $t('project.submit_loading_sites') : $t('project.submit_refresh_sites') }}
                                                </button>
                                            </div>
                                            <select
                                                v-model="submitForm.bing_site_url"
                                                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            >
                                                <option value="">{{ $t('project.submit_select_bing_site') }}</option>
                                                <option v-for="site in bingSites" :key="site.site_url" :value="site.site_url">
                                                    {{ site.site_url }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <PrimaryButton type="button" @click="enviarSitemapBing" :disabled="enviandoBing || !submitForm.bing_site_url">
                                                {{ enviandoBing ? $t('project.submit_sending') : $t('project.submit_bing_action') }}
                                            </PrimaryButton>
                                            <button type="button" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="removerBingWebmasterKey" :disabled="removendoBing">
                                                {{ removendoBing ? $t('project.submit_disconnecting') : $t('project.submit_bing_disconnect') }}
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else class="space-y-4">
                                        <p class="text-sm text-gray-500">{{ $t('project.submit_bing_connect_desc') }}</p>
                                        <input
                                            v-model="submitForm.bing_api_key"
                                            type="password"
                                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                            :placeholder="$t('project.submit_bing_key_placeholder')"
                                        />
                                        <PrimaryButton type="button" @click="salvarBingWebmasterKey" :disabled="salvandoBingKey || !submitForm.bing_api_key">
                                            {{ salvandoBingKey ? $t('project.submit_connecting') : $t('project.submit_bing_connect') }}
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">
                                <div>
                                    <h4 class="text-base font-bold text-gray-800">{{ $t('project.submit_history_title') }}</h4>
                                    <p class="text-sm text-gray-500">{{ $t('project.submit_history_intro') }}</p>
                                </div>

                                <div v-if="submissionHistory.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-8 text-center text-sm text-gray-500">
                                    {{ $t('project.submit_history_empty') }}
                                </div>

                                <div v-else class="space-y-3">
                                    <div v-for="submission in submissionHistory" :key="submission.id" class="rounded-lg border border-gray-200 px-4 py-4">
                                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-800 uppercase">{{ submission.provider }}</span>
                                                    <span :class="['inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide', submissionStatusClass(submission.status)]">
                                                        {{ submissionStatusLabel(submission.status) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-700">{{ submission.site_identifier }}</p>
                                                <p class="text-xs text-gray-500 break-all">{{ submission.sitemap_url }}</p>
                                                <p v-if="submission.message" class="text-xs text-gray-500">{{ submission.message }}</p>
                                            </div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400">
                                                {{ formatarDataHora(submission.submitted_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="abaAtiva === 'history'" class="space-y-6">
                            <div class="bg-primary-50 border border-primary-100 rounded-lg p-5">
                                <h3 class="text-lg font-bold text-primary-900 mb-2">{{ $t('project.history_title') }}</h3>
                                <p class="text-sm text-primary-800">{{ $t('project.history_intro') }}</p>
                            </div>

                            <div v-if="historicoJobsVisiveis.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500">
                                {{ $t('project.history_empty') }}
                            </div>

                            <div v-else class="space-y-4">
                                <article v-for="(job, index) in historicoJobsVisiveis" :key="job.id ?? job.external_job_id ?? index" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                                    <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span :class="['inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wide', statusClasseJob(job.status)]">
                                                    {{ statusLabelJob(job.status) }}
                                                </span>
                                                <span v-if="jobMaisRecente(job, index)" class="inline-flex items-center rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-primary-700">
                                                    {{ $t('project.history_latest') }}
                                                </span>
                                            </div>
                                            <div class="mt-3 text-xs font-bold uppercase tracking-wide text-gray-500">{{ $t('project.history_job_id') }}</div>
                                            <div class="mt-1 break-all text-sm font-semibold text-gray-800">{{ job.external_job_id || '-' }}</div>
                                            <p class="mt-2 text-sm text-gray-500">{{ job.message || $t('project.history_no_message') }}</p>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 text-sm text-gray-600 sm:grid-cols-3 lg:min-w-[420px]">
                                            <div>
                                                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t('project.history_started_at') }}</div>
                                                <div class="mt-1 font-medium text-gray-800">{{ formatarDataHora(job.started_at) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t('project.history_completed_at') }}</div>
                                                <div class="mt-1 font-medium text-gray-800">{{ formatarDataHora(job.completed_at) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t('project.history_duration') }}</div>
                                                <div class="mt-1 font-medium text-gray-800">{{ formatarDuracaoJob(job) }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 px-5 py-4 lg:grid-cols-4">
                                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t('project.total_indexed') }}</div>
                                            <div class="mt-1 text-xl font-bold text-gray-800">{{ urlsIndexadasDoJob(job) }}</div>
                                        </div>
                                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t('project.pages_discovered') }}</div>
                                            <div class="mt-1 text-xl font-bold text-gray-800">{{ urlsDescobertasDoJob(job) }}</div>
                                        </div>
                                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t('crawler.skipped') }}</div>
                                            <div class="mt-1 text-xl font-bold text-gray-800">{{ job.urls_excluded ?? 0 }}</div>
                                        </div>
                                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t('crawler.pages_processed') }}</div>
                                            <div class="mt-1 text-xl font-bold text-gray-800">{{ job.urls_crawled ?? 0 }}</div>
                                        </div>
                                    </div>

                                    <div v-if="job.artifacts?.length" class="border-t border-gray-100 px-5 py-4">
                                        <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t('project.history_artifacts') }}</div>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <a v-for="artifact in job.artifacts" :key="`${job.external_job_id}-${artifact.name}`" :href="artifact.download_url" target="_blank" class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-primary-300 hover:text-primary-700">
                                                <span class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-bold uppercase text-gray-500">{{ artifact.name?.split('.').pop() }}</span>
                                                <span>{{ artifact.name }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div v-else-if="abaAtiva === 'settings'" class="space-y-8">
                            <div class="bg-primary-50 border border-primary-100 rounded-lg p-5">
                                <h3 class="text-lg font-bold text-primary-900 mb-2">{{ $t('project.settings_title') }}</h3>
                                <p class="text-sm text-primary-800">
                                    {{ $t('project.settings_intro') }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="border border-gray-200 rounded-lg p-5 bg-white">
                                    <h4 class="text-sm font-bold uppercase tracking-wide text-gray-700 mb-4">{{ $t('project.settings_basic_title') }}</h4>

                                    <div class="space-y-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_frequency') }}</label>
                                            <select v-model="configForm.frequency" class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                                <option v-for="frequency in allowedFrequencyOptions" :key="frequency" :value="frequency">
                                                    {{ frequencyLabel(frequency) }}
                                                </option>
                                            </select>
                                            <p class="mt-2 text-xs text-gray-500">{{ $t('project.field_frequency_help') }}</p>
                                            <div v-if="usaFrequenciaCustomizada" class="mt-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_custom_interval_hours') }}</label>
                                                <input
                                                    v-model.number="configForm.intervalo_personalizado_horas"
                                                    type="number"
                                                    :min="intervaloPersonalizadoMinimoHoras"
                                                    :max="intervaloPersonalizadoMaximoHoras"
                                                    class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                                />
                                                <p class="mt-2 text-xs text-gray-500">
                                                    {{ $t('project.field_custom_interval_hours_help', { min: intervaloPersonalizadoMinimoHoras, max: intervaloPersonalizadoMaximoHoras }) }}
                                                </p>
                                            </div>
                                            <div class="mt-3 rounded-md border border-primary-100 bg-primary-50 px-4 py-3">
                                                <div class="text-[11px] font-bold uppercase tracking-wide text-primary-700">{{ $t('project.next_scheduled_run_label') }}</div>
                                                <div class="mt-1 text-sm font-medium text-primary-900">{{ proximoRastreamentoDescricao }}</div>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_max_pages') }}</label>
                                            <input v-model.number="configForm.max_pages" type="number" min="1" :max="planMaxPages" class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500" />
                                            <p class="mt-2 text-xs text-gray-500">{{ $t('project.settings_plan_limit', { count: planMaxPages }) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="border border-gray-200 rounded-lg p-5 bg-white">
                                    <div class="flex items-center justify-between gap-3 mb-4">
                                        <h4 class="text-sm font-bold uppercase tracking-wide text-gray-700">{{ $t('project.settings_advanced_title') }}</h4>
                                        <span v-if="!canEditAdvancedSettings" class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-gray-100 text-gray-500 border border-gray-200">
                                            {{ $t('project.pro_feature') }}
                                        </span>
                                    </div>

                                    <div v-if="!canEditAdvancedSettings" class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                        {{ $t('project.settings_locked') }}
                                    </div>

                                    <div class="space-y-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_max_depth') }}</label>
                                            <input v-model.number="configForm.max_depth" type="number" min="1" :max="features.max_depth_limit || 10" :disabled="!canEditAdvancedSettings" class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-500" />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_concurrency') }}</label>
                                            <input v-model.number="configForm.max_concurrent_requests" type="number" min="1" :max="features.max_concurrent_requests_limit || 10" :disabled="!canEditAdvancedSettings" class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-500" />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_delay') }}</label>
                                            <input v-model.number="configForm.delay_between_requests" type="number" step="0.1" :min="features.delay_between_requests_min ?? 0" :max="features.delay_between_requests_max ?? 10" :disabled="!canEditAdvancedSettings" class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-500" />
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_user_agent') }}</label>
                                            <input v-model="configForm.user_agent_custom" type="text" maxlength="255" :disabled="!canEditAdvancedSettings" class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-500" :placeholder="$t('project.field_user_agent_placeholder')" />
                                            <p class="mt-2 text-xs text-gray-500">{{ $t('project.field_user_agent_help') }}</p>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <label class="flex items-start gap-3 rounded-md border border-gray-200 px-4 py-3" :class="{ 'opacity-60': !permiteNoticias }">
                                                <input v-model="configForm.check_news" type="checkbox" :disabled="!permiteNoticias" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                                <span>
                                                    <span class="block text-sm font-medium text-gray-700">{{ $t('project.field_news') }}</span>
                                                    <span class="mt-1 block text-xs text-gray-500">{{ $t('project.field_news_help') }}</span>
                                                </span>
                                            </label>

                                            <label class="flex items-start gap-3 rounded-md border border-gray-200 px-4 py-3" :class="{ 'opacity-60': !permiteMobile }">
                                                <input v-model="configForm.check_mobile" type="checkbox" :disabled="!permiteMobile" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                                <span>
                                                    <span class="block text-sm font-medium text-gray-700">{{ $t('project.field_mobile') }}</span>
                                                    <span class="mt-1 block text-xs text-gray-500">{{ $t('project.field_mobile_help') }}</span>
                                                </span>
                                            </label>

                                            <label class="flex items-start gap-3 rounded-md border border-gray-200 px-4 py-3" :class="{ 'opacity-60': !permiteCompactacao }">
                                                <input v-model="configForm.compress_output" type="checkbox" :disabled="!permiteCompactacao" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                                <span>
                                                    <span class="block text-sm font-medium text-gray-700">{{ $t('project.field_compress_output') }}</span>
                                                    <span class="mt-1 block text-xs text-gray-500">{{ $t('project.field_compress_output_help') }}</span>
                                                </span>
                                            </label>

                                            <label class="flex items-start gap-3 rounded-md border border-gray-200 px-4 py-3" :class="{ 'opacity-60': !permiteCacheCrawler }">
                                                <input v-model="configForm.enable_cache" type="checkbox" :disabled="!permiteCacheCrawler" class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                                <span>
                                                    <span class="block text-sm font-medium text-gray-700">{{ $t('project.field_enable_cache') }}</span>
                                                    <span class="mt-1 block text-xs text-gray-500">{{ $t('project.field_enable_cache_help') }}</span>
                                                </span>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_crawl_policy') }}</label>
                                            <select v-model="configForm.crawl_policy_id" :disabled="!permitePoliticasCrawl" class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-500">
                                                <option value="">{{ $t('project.field_no_crawl_policy') }}</option>
                                                <option v-for="preset in presetsPoliticaCrawl" :key="preset.id" :value="preset.id">
                                                    {{ preset.name }}
                                                </option>
                                            </select>
                                            <p class="mt-2 text-xs text-gray-500">{{ $t('project.field_crawl_policy_help') }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('project.field_exclude_patterns') }}</label>
                                            <textarea
                                                v-model="textoPadroesExclusao"
                                                rows="5"
                                                :disabled="!permitePadroesExclusao"
                                                class="w-full rounded-md border-gray-300 focus:border-primary-500 focus:ring-primary-500 disabled:bg-gray-100 disabled:text-gray-500"
                                                :placeholder="$t('project.field_exclude_patterns_placeholder')"
                                            />
                                            <p class="mt-2 text-xs text-gray-500">{{ $t('project.field_exclude_patterns_help') }}</p>
                                            <p v-if="exemplosPoliticaCrawl.length" class="mt-2 text-xs text-gray-500">
                                                {{ $t('project.field_exclude_patterns_examples') }}:
                                                <span class="font-mono">{{ exemplosPoliticaCrawl.join(', ') }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button @click="salvarConfiguracoes" :disabled="salvandoConfiguracoes" class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-sm transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ salvandoConfiguracoes ? $t('project.settings_saving') : $t('project.settings_save') }}
                                </button>
                            </div>
                        </div>

                    </div>

                </div>

                <Link :href="route('dashboard')"
                    class="text-gray-500 hover:text-primary-600 transition flex items-center justify-end gap-2 mr-4 mt-4 "
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
