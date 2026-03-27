<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    apiKey:         { type: String, default: null },
    endpointUrl:    { type: String, default: '' },
    callbackUrl:    { type: String, default: '' },
    projetos:       { type: Array,  default: () => [] },
    podeAcessarApi: { type: Boolean, default: false },
});

// Controle de abas
const activeTab = ref('reference');

// --------------------
// SETUP API
// --------------------
const selectedProjeto = ref('');
const callbackForm = useForm({ callback_url: props.callbackUrl || '' });

const resetando   = ref(false);
const chaveCriada = ref(null);
const chaveAtual  = computed(() => chaveCriada.value ?? props.apiKey);

const resetarChave = () => {
    if (!confirm('Isso invalidará sua chave atual. Continuar?')) return;
    resetando.value = true;
    router.post(route('account.api.reset-key'), {}, {
        preserveScroll: true,
        onFinish: () => { resetando.value = false; },
        onSuccess: (page) => {
            // A nova chave vem via flash ou reload — recarregamos a página
            router.reload({ only: ['apiKey'] });
        },
    });
};

const salvarCallback = () => {
    callbackForm.post(route('account.api.callback-url'), { preserveScroll: true });
};

// --------------------
// API REFERENCE — dados dos endpoints
// --------------------
const exemploProjId = computed(() => {
    if (selectedProjeto.value && props.projetos.length) {
        const p = props.projetos.find(p => p.id == selectedProjeto.value);
        return p ? p.id : '4673908';
    }
    return '4673908';
});

const exemploProjUrl = computed(() => {
    if (selectedProjeto.value && props.projetos.length) {
        const p = props.projetos.find(p => p.id == selectedProjeto.value);
        return p ? p.url : 'https://www.yourdomain.com/';
    }
    return 'https://www.yourdomain.com/';
});

const apiKeyDisplay = computed(() => chaveAtual.value || 'ps_IfphlsHs.IQBliAaHJV7iX8hzHsabhWLONyz7cnP8r8sSxVpFAVigMEh6');

const endpoints = computed(() => [
    {
        id:     'site_add',
        titulo: trans('api.ref.ep.site_add'),
        requestParams: [
            { param: 'method',  desc: trans('api.ref.param.method'), value: 'site_add' },
            { param: 'api_key', desc: trans('api.ref.param.api_key'), value: apiKeyDisplay.value },
            { param: 'uri',     desc: trans('api.ref.param.uri'), value: exemploProjUrl.value },
        ],
        responseParams: [
            { param: 'api_success', desc: trans('api.ref.resp.api_success'), value: 'true/false' },
            { param: 'time',        desc: trans('api.ref.resp.time'),         value: '2026-03-27T11:00:23+00:00' },
            { param: 'result',      desc: trans('api.ref.resp.result'), value: null, json: `{\n  "result": [\n    {\n      "new_site_id": integer,\n      "new_url": "${exemploProjUrl.value}",\n      "url": "${exemploProjUrl.value}",\n      "messages": [ ... ],\n      "errors": [\n        "Entry for this domain already exists in your account"\n      ]\n    }\n  ]\n}` },
            { param: 'result_desc', desc: trans('api.ref.resp.result_desc'), value: '' },
        ],
    },
    {
        id:     'sites_list',
        titulo: trans('api.ref.ep.sites_list'),
        requestParams: [
            { param: 'method',  desc: trans('api.ref.param.method'),  value: 'sites_list' },
            { param: 'api_key', desc: trans('api.ref.param.api_key'), value: apiKeyDisplay.value },
        ],
        responseParams: [
            { param: 'api_success', desc: trans('api.ref.resp.api_success'), value: 'true/false' },
            { param: 'time',        desc: trans('api.ref.resp.time'),         value: '2026-03-27T11:00:23+00:00' },
            { param: 'result',      desc: trans('api.ref.resp.result'), value: null, json: `{\n  "entries": [\n    {\n      "id": "38275",\n      "url": "${exemploProjUrl.value}",\n      "created": 1515046557,\n      "pro_account": true/false,\n      "sitemap_updated": 1515046557,\n      "pages_indexed": 20\n    },\n    ...\n  ],\n  "total_entries": 80\n}` },
            { param: 'result_desc', desc: trans('api.ref.resp.result_desc'), value: '' },
        ],
    },
    {
        id:     'get_sitemap',
        titulo: trans('api.ref.ep.get_sitemap'),
        requestParams: [
            { param: 'method',  desc: trans('api.ref.param.method'),  value: 'get_sitemap' },
            { param: 'api_key', desc: trans('api.ref.param.api_key'), value: apiKeyDisplay.value },
            { param: 'site_id', desc: trans('api.ref.param.site_id'), value: exemploProjId.value },
        ],
        responseParams: [
            { param: 'api_success', desc: trans('api.ref.resp.api_success'), value: 'true/false' },
            { param: 'time',        desc: trans('api.ref.resp.time'),         value: '2026-03-27T11:00:23+00:00' },
            { param: 'site_id',     desc: trans('api.ref.resp.site_id'),      value: exemploProjId.value },
            { param: 'result',      desc: trans('api.ref.resp.result'), value: null, json: `{\n  "sitemap_updated": "2026-03-27T11:00:23+00:00",\n  "sitemap_pages": "500",\n  "sitemap_urls": [\n    "https://a560342.sitemaphosting7.com/${exemploProjId.value}/sitemap_${exemploProjId.value}.xml"\n  ]\n}` },
            { param: 'result_desc', desc: trans('api.ref.resp.result_desc'), value: '' },
        ],
    },
    {
        id:     'download_sitemap',
        titulo: trans('api.ref.ep.download_sitemap'),
        isRaw:  true,
        requestParams: [
            { param: 'method',                   desc: trans('api.ref.param.method'),            value: 'download_sitemap' },
            { param: 'api_key',                  desc: trans('api.ref.param.api_key'),           value: apiKeyDisplay.value },
            { param: 'site_id',                  desc: trans('api.ref.param.site_id'),           value: exemploProjId.value },
            { param: 'sitemap_self_uri',          desc: trans('api.ref.param.sitemap_self_uri'),  value: 'https://www.yourwebsite.com/your-script.php' },
            { param: 'sitemap_self_path (optional)', desc: trans('api.ref.param.sitemap_self_path'), value: 'https://www.yourwebsite.com/sitemap/' },
            { param: 'sitemap_id (optional)',        desc: trans('api.ref.param.sitemap_id'),        value: 'sitemap_images.xml' },
        ],
        responseParams: [],
    },
    {
        id:     'download_sitemap_all',
        titulo: trans('api.ref.ep.download_sitemap_all'),
        isZip:  true,
        requestParams: [
            { param: 'method',  desc: trans('api.ref.param.method'),  value: 'download_sitemap_all' },
            { param: 'api_key', desc: trans('api.ref.param.api_key'), value: apiKeyDisplay.value },
            { param: 'site_id', desc: trans('api.ref.param.site_id'), value: exemploProjId.value },
        ],
        responseParams: [],
    },
    {
        id:     'site_history',
        titulo: trans('api.ref.ep.site_history'),
        requestParams: [
            { param: 'method',          desc: trans('api.ref.param.method'),  value: 'site_history' },
            { param: 'api_key',         desc: trans('api.ref.param.api_key'), value: apiKeyDisplay.value },
            { param: 'site_id',         desc: trans('api.ref.param.site_id'), value: exemploProjId.value },
            { param: 'from (optional)', desc: trans('api.ref.param.from'),    value: '0' },
        ],
        responseParams: [
            { param: 'api_success', desc: trans('api.ref.resp.api_success'), value: 'true/false' },
            { param: 'time',        desc: trans('api.ref.resp.time'),         value: '2026-03-27T11:00:23+00:00' },
            { param: 'site_id',     desc: trans('api.ref.resp.site_id'),      value: exemploProjId.value },
            { param: 'result',      desc: trans('api.ref.resp.result'), value: null, json: `{\n  "entries": [\n    {\n      "id": "38275",\n      "time": 1515046557,\n      "pages_indexed": 97,\n      "pages_crawled": 117,\n      "pages_skipped": 20,\n      "pages_fetched": 114,\n      "pages_added": 0,\n      "pages_removed": 0,\n      "image_count": 8,\n      "video_count": 17,\n      "rss_count": 1,\n      "news_count": null,\n      "broken_links": 18,\n      "processing_time": 4.0925,\n      "processing_bandwidth": 928411\n    },\n    ...\n  ],\n  "total_entries": 80\n}` },
            { param: 'result_desc', desc: trans('api.ref.resp.result_desc'), value: '' },
        ],
    },
    {
        id:     'site_history_detail',
        titulo: trans('api.ref.ep.site_history_detail'),
        requestParams: [
            { param: 'method',   desc: trans('api.ref.param.method'),   value: 'site_history' },
            { param: 'api_key',  desc: trans('api.ref.param.api_key'),  value: apiKeyDisplay.value },
            { param: 'site_id',  desc: trans('api.ref.param.site_id'),  value: exemploProjId.value },
            { param: 'entry_id', desc: trans('api.ref.param.entry_id'), value: 'number' },
        ],
        responseParams: [
            { param: 'api_success', desc: trans('api.ref.resp.api_success'), value: 'true/false' },
            { param: 'time',        desc: trans('api.ref.resp.time'),         value: '2026-03-27T11:00:23+00:00' },
            { param: 'site_id',     desc: trans('api.ref.resp.site_id'),      value: exemploProjId.value },
            { param: 'result',      desc: trans('api.ref.resp.result'), value: null, json: `{\n  "entry_details": {\n    "info": {\n      "id": "38275",\n      "time": 1515046557,\n      "pages_indexed": 97,\n      "pages_crawled": 117,\n      "pages_skipped": 20\n      // ...\n    },\n    "new_urls": ["url1", "url2"],\n    "removed_urls": ["url1", "url2"],\n    "skipped_urls": [{ "url1", "skip_reason1" }],\n    "new_images": [{ "page_url1", "image_url1" }],\n    "removed_images": [{ "page_url1", "image_url1" }],\n    "new_videos": [{ "page_url1", "video_url1" }],\n    "removed_videos": [{ "page_url1", "video_url1" }],\n    "broken_links": [{ "url1", "referringpage1" }]\n  }\n}` },
        ],
    },
    {
        id:     'request_update',
        titulo: trans('api.ref.ep.request_update'),
        requestParams: [
            { param: 'method',  desc: trans('api.ref.param.method'),  value: 'request_sitemap_update' },
            { param: 'api_key', desc: trans('api.ref.param.api_key'), value: apiKeyDisplay.value },
            { param: 'site_id', desc: trans('api.ref.param.site_id'), value: exemploProjId.value },
        ],
        responseParams: [
            { param: 'api_success', desc: trans('api.ref.resp.api_success'), value: 'true/false' },
            { param: 'time',        desc: trans('api.ref.resp.time'),         value: '2026-03-27T11:00:23+00:00' },
            { param: 'site_id',     desc: trans('api.ref.resp.site_id'),      value: exemploProjId.value },
            { param: 'result',      desc: trans('api.ref.resp.result'), value: null, json: `{\n  "update_scheduled": true/false,\n  "message": "Sitemap update has been scheduled."\n}` },
            { param: 'result_desc', desc: trans('api.ref.resp.result_desc'), value: '' },
        ],
    },
    {
        id:     'set_notification',
        titulo: trans('api.ref.ep.set_notification'),
        requestParams: [
            { param: 'method',       desc: trans('api.ref.param.method'),  value: 'set_notification' },
            { param: 'api_key',      desc: trans('api.ref.param.api_key'), value: apiKeyDisplay.value },
            { param: 'site_id',      desc: trans('api.ref.param.site_id'), value: exemploProjId.value },
            { param: 'callback_url', desc: trans('api.ref.param.callback_url_desc'), value: 'https://yoursite.com/my-callback/' },
        ],
        responseParams: [
            { param: 'api_success', desc: trans('api.ref.resp.api_success'), value: 'true/false' },
            { param: 'time',        desc: trans('api.ref.resp.time'),         value: '2026-03-27T11:00:23+00:00' },
            { param: 'result',      desc: trans('api.ref.resp.result'), value: null, json: `{\n  "notification_set": true/false\n}` },
            { param: 'result_desc', desc: trans('api.ref.resp.result_desc'), value: '' },
        ],
    },
]);

// Índice navegável — reativo para responder à troca de idioma
const indice = computed(() => [
    { label: trans('api.ref.section1'), children: [
        { id: 'site_add',   label: trans('api.ref.method1') },
        { id: 'sites_list', label: trans('api.ref.method2') },
    ]},
    { label: trans('api.ref.section2'), children: [
        { id: 'get_sitemap',          label: trans('api.ref.method3') },
        { id: 'download_sitemap',     label: trans('api.ref.method4') },
        { id: 'download_sitemap_all', label: trans('api.ref.method5') },
        { id: 'site_history',         label: trans('api.ref.method6') },
        { id: 'site_history_detail',  label: trans('api.ref.method7') },
        { id: 'request_update',       label: trans('api.ref.method8') },
        { id: 'set_notification',     label: trans('api.ref.method9') },
    ]},
]);

const scrollTo = (id) => {
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <Head title="API" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">API</h2>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">

                    <!-- Título -->
                    <div class="text-center py-4 border-b border-gray-200">
                        <h1 class="text-lg font-bold text-gray-700 uppercase tracking-wide">{{ $t('api.title_internal') }}</h1>
                    </div>

                    <!-- Abas -->
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex">
                            <button
                                @click="activeTab = 'reference'"
                                :class="[
                                    activeTab === 'reference'
                                        ? 'border-primary-500 text-primary-600 bg-white'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-3 px-1 text-center border-b-2 font-semibold text-sm tracking-wide transition-colors duration-200'
                                ]"
                            >
                                {{ $t('api.tab_reference') }}
                            </button>
                            <button
                                @click="activeTab = 'setup'"
                                :class="[
                                    activeTab === 'setup'
                                        ? 'border-primary-500 text-primary-600 bg-white'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-3 px-1 text-center border-b-2 font-semibold text-sm tracking-wide transition-colors duration-200'
                                ]"
                            >
                                {{ $t('api.tab_setup') }}
                            </button>
                        </nav>
                    </div>

                    <!-- ==================== ABA: API REFERENCE ==================== -->
                    <div v-show="activeTab === 'reference'" class="p-6">

                        <!-- Intro -->
                        <p class="text-sm text-gray-700 mb-2">{{ $t('api.ref.intro') }}</p>
                        <p class="text-sm text-gray-600 mb-4">
                            {{ $t('api.ref.visit_setup') }}
                            <button @click="activeTab = 'setup'" class="text-accent-600 font-semibold hover:underline">{{ $t('api.ref.setup_link') }}</button>
                            {{ $t('api.ref.setup_suffix') }}
                        </p>

                        <!-- Índice Navegável -->
                        <div class="mb-6 text-sm">
                            <div v-for="grupo in indice" :key="grupo.label" class="mb-2">
                                <p class="font-semibold text-gray-600 mb-1">{{ grupo.label }}</p>
                                <ul class="ml-4 space-y-0.5">
                                    <li v-for="item in grupo.children" :key="item.id">
                                        <button
                                            @click="scrollTo(item.id)"
                                            class="text-accent-600 hover:underline text-left"
                                        >
                                            {{ item.label }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Seletor de Projeto (site-specific) -->
                        <div v-if="projetos.length > 0" class="mb-6 p-3 border border-gray-200 rounded bg-gray-50 text-sm">
                            <p class="text-gray-600 mb-2">{{ $t('api.setup.switch_site') }}</p>
                            <select
                                v-model="selectedProjeto"
                                class="border border-gray-300 rounded px-3 py-1.5 text-sm w-72 focus:outline-none focus:ring-1 focus:ring-primary-400"
                            >
                                <option value="">{{ $t('api.setup.select_site') }}</option>
                                <option v-for="p in projetos" :key="p.id" :value="p.id">{{ p.url }}</option>
                            </select>
                        </div>

                        <!-- Endpoints -->
                        <div class="space-y-0">
                            <div v-for="ep in endpoints" :key="ep.id" :id="ep.id" class="border border-gray-200 rounded mb-4">

                                <!-- Cabeçalho do endpoint -->
                                <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b border-gray-200">
                                    <span class="text-sm font-semibold text-gray-700">{{ ep.titulo }}</span>
                                    <button
                                        @click="activeTab = 'setup'"
                                        class="flex items-center gap-1 text-xs text-accent-600 hover:underline"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        {{ $t('api.ref.launch_testing') }}
                                    </button>
                                </div>

                                <!-- Request -->
                                <div class="border-b border-gray-100">
                                    <div class="px-4 py-2 bg-blue-50 text-xs text-gray-600 border-b border-blue-100 flex items-center gap-2">
                                        <span class="text-blue-500 font-bold">→</span>
                                        {{ $t('api.ref.request_params') }}
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="border-b border-gray-100 bg-gray-50">
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600 w-1/4">{{ $t('api.ref.col_param') }}</th>
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600 w-1/3">{{ $t('api.ref.col_desc') }}</th>
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600">{{ $t('api.ref.col_value') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="p in ep.requestParams" :key="p.param" class="border-b border-gray-50 hover:bg-gray-50">
                                                    <td class="px-4 py-2 font-mono text-gray-700 align-top">{{ p.param }}</td>
                                                    <td class="px-4 py-2 text-gray-500 align-top">{{ p.desc }}</td>
                                                    <td class="px-4 py-2 text-gray-700 align-top break-all">
                                                        <span v-if="p.param === 'api_key'" class="font-mono text-[10px] break-all">{{ p.value }}</span>
                                                        <span v-else>{{ p.value }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Response -->
                                <div v-if="ep.isRaw" class="px-4 py-3 text-xs text-gray-600 bg-blue-50 flex items-center gap-2">
                                    <span class="text-blue-500 font-bold">←</span>
                                    {{ $t('api.ref.raw_response') }}
                                </div>
                                <div v-else-if="ep.isZip" class="px-4 py-3 text-xs text-gray-600 bg-blue-50 flex items-center gap-2">
                                    <span class="text-blue-500 font-bold">←</span>
                                    {{ $t('api.ref.zip_response') }}
                                </div>
                                <div v-else-if="ep.responseParams && ep.responseParams.length">
                                    <div class="px-4 py-2 bg-blue-50 text-xs text-gray-600 border-b border-blue-100 flex items-center gap-2">
                                        <span class="text-blue-500 font-bold">←</span>
                                        {{ $t('api.ref.response_params') }}
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="border-b border-gray-100 bg-gray-50">
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600 w-1/4">{{ $t('api.ref.col_param') }}</th>
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600 w-1/3">{{ $t('api.ref.col_desc') }}</th>
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600">{{ $t('api.ref.col_value') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="p in ep.responseParams" :key="p.param" class="border-b border-gray-50 hover:bg-gray-50">
                                                    <td class="px-4 py-2 font-mono text-gray-700 align-top">{{ p.param }}</td>
                                                    <td class="px-4 py-2 text-gray-500 align-top">{{ p.desc }}</td>
                                                    <td class="px-4 py-2 align-top">
                                                        <pre v-if="p.json" class="bg-blue-50 text-blue-800 rounded p-2 text-[10px] overflow-x-auto whitespace-pre-wrap font-mono leading-relaxed">{{ p.json }}</pre>
                                                        <span v-else-if="p.param === 'result_desc'" class="text-accent-600 italic">{{ p.desc }}</span>
                                                        <span v-else class="text-gray-700">{{ p.value }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== ABA: SETUP API ==================== -->
                    <div v-show="activeTab === 'setup'" class="p-6 space-y-6">

                        <!-- Upgrade notice -->
                        <div v-if="!podeAcessarApi" class="bg-amber-50 border border-amber-200 rounded p-4 text-sm text-amber-800">
                            {{ $t('api.setup.upgrade_notice') }}
                        </div>

                        <!-- Intro -->
                        <div class="text-sm text-gray-700 space-y-1">
                            <p>{{ $t('api.setup.intro1') }}</p>
                            <p>
                                {{ $t('api.setup.intro2') }}
                                <button @click="activeTab = 'reference'" class="text-accent-600 font-semibold underline">{{ $t('api.setup.guide_link') }}</button>
                                {{ $t('api.setup.intro2_suffix') }}
                            </p>
                        </div>

                        <!-- Nota -->
                        <div class="text-center text-sm py-3 border border-gray-200 rounded bg-gray-50 text-gray-600">
                            {{ $t('api.setup.note_plans') }}
                        </div>

                        <!-- Switch to site-specific -->
                        <div class="border border-gray-200 rounded">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
                                {{ $t('api.setup.switch_site') }}
                            </div>
                            <div class="px-4 py-3">
                                <select
                                    v-model="selectedProjeto"
                                    class="border border-gray-300 rounded px-3 py-1.5 text-sm w-80 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                >
                                    <option value="">{{ $t('api.setup.select_site') }}</option>
                                    <option v-for="p in projetos" :key="p.id" :value="p.id">{{ p.url }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Chave de API -->
                        <div class="border border-gray-200 rounded">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
                                {{ $t('api.setup.your_key') }}
                            </div>
                            <div class="px-4 py-4 space-y-3">
                                <div v-if="chaveAtual">
                                    <input
                                        type="text"
                                        :value="chaveAtual"
                                        readonly
                                        class="w-full font-mono text-xs border border-gray-200 rounded px-3 py-2 bg-gray-50 text-gray-700 select-all"
                                    />
                                    <p class="text-xs text-gray-600 mt-2">
                                        <button
                                            @click="resetarChave"
                                            :disabled="resetando"
                                            class="text-accent-600 font-bold hover:underline disabled:opacity-50"
                                        >
                                            {{ $t('api.setup.reset_link') }}
                                        </button>
                                        {{ $t('api.setup.reset_text') }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $t('api.setup.reset_warning') }}</p>
                                </div>
                                <div v-else>
                                    <p class="text-sm text-gray-500 mb-3">{{ $t('api.setup.no_key') }}</p>
                                    <button
                                        @click="resetarChave"
                                        :disabled="resetando"
                                        class="bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold py-2 px-4 rounded uppercase tracking-wider transition disabled:opacity-50"
                                    >
                                        {{ resetando ? $t('api.setup.generating') : $t('api.setup.generate_key') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Endpoint URL -->
                        <div class="border border-gray-200 rounded">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
                                {{ $t('api.setup.endpoint_title') }}
                            </div>
                            <div class="px-4 py-4 space-y-2 text-sm text-gray-700">
                                <p>
                                    {{ $t('api.setup.endpoint_info1') }}
                                    <strong class="text-accent-700">POST</strong>
                                    {{ $t('api.setup.endpoint_info1').includes('método') ? '' : ' method' }}
                                </p>
                                <p>
                                    {{ $t('api.setup.endpoint_info2') }}
                                    <strong>application/x-www-form-urlencoded</strong>
                                    content-type
                                </p>
                                <p>{{ $t('api.setup.endpoint_info3') }}</p>
                                <div class="border border-gray-200 rounded px-3 py-2 font-mono text-xs bg-gray-50 text-gray-600">
                                    {{ endpointUrl }}
                                </div>
                            </div>
                        </div>

                        <!-- Callback URL -->
                        <div class="border border-gray-200 rounded">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
                                {{ $t('api.setup.callback_title') }}
                            </div>
                            <div class="px-4 py-4 space-y-3">
                                <p class="text-sm text-gray-600">
                                    {{ $t('api.setup.callback_desc') }}
                                    <strong>{{ $t('api.setup.callback_bold') }}</strong>
                                    {{ $t('api.setup.callback_suffix') }}
                                </p>
                                <form @submit.prevent="salvarCallback">
                                    <input
                                        v-model="callbackForm.callback_url"
                                        type="url"
                                        :placeholder="$t('api.setup.callback_placeholder')"
                                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary-400 mb-3"
                                    />
                                    <div class="flex items-center gap-4">
                                        <button
                                            type="submit"
                                            :disabled="callbackForm.processing"
                                            class="bg-primary-700 hover:bg-primary-800 text-white text-xs font-bold py-2.5 px-5 rounded uppercase tracking-wider transition disabled:opacity-50"
                                        >
                                            {{ callbackForm.processing ? $t('api.setup.saving') : $t('api.setup.save_callback') }}
                                        </button>
                                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                            <p v-if="callbackForm.recentlySuccessful" class="text-sm text-green-600">{{ $t('api.setup.saved') }}</p>
                                        </Transition>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
