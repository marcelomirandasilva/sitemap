<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const appName = computed(() => page.props.appName || 'PRO Sitemaps');

const props = defineProps({
    apiKey:         { type: String, default: null },
    endpointUrl:    { type: String, default: '' },
    callbackUrl:    { type: String, default: '' },
    projetos:       { type: Array,  default: () => [] },
    podeAcessarApi: { type: Boolean, default: false },
});

const activeTab = ref('reference');
const selectedProjeto = ref('');
const resetando = ref(false);
const exemploJobId = '550e8400-e29b-41d4-a716-446655440000';

const chaveAtual = computed(() => props.apiKey);
const apiKeyDisplay = computed(() => chaveAtual.value || 'YOUR_API_KEY_HERE');
const authorizationHeader = computed(() => `Bearer ${apiKeyDisplay.value}`);
const endpointBase = computed(() => `${props.endpointUrl}/sitemaps`);

const projetoSelecionado = computed(() => {
    if (!selectedProjeto.value || !props.projetos.length) {
        return null;
    }

    return props.projetos.find((projeto) => projeto.id == selectedProjeto.value) || null;
});

const exemploProjId = computed(() => projetoSelecionado.value?.id || '4673908');
const exemploProjUrl = computed(() => projetoSelecionado.value?.url || 'https://www.yourdomain.com/');
const outputDirectory = computed(() => `sitemaps/projects/${exemploProjId.value}`);

const indice = [
    {
        label: '1. Fluxo do job',
        children: [
            { id: 'create_job', label: '1.1 Criar job de sitemap' },
            { id: 'get_status', label: '1.2 Consultar status do job' },
        ],
    },
    {
        label: '2. Artefatos e controle',
        children: [
            { id: 'list_artifacts', label: '2.1 Listar artefatos' },
            { id: 'download_artifact', label: '2.2 Baixar artefato' },
            { id: 'cancel_job', label: '2.3 Cancelar job' },
        ],
    },
];

const endpoints = computed(() => [
    {
        id: 'create_job',
        titulo: 'POST /sitemaps',
        requestParams: [
            { param: 'Authorization', desc: 'Header HTTP com sua API Key no formato Bearer.', value: authorizationHeader.value },
            { param: 'Content-Type', desc: 'Envie o payload em JSON.', value: 'application/json' },
            { param: 'start_urls', desc: 'Lista de URLs iniciais do rastreamento.', value: `["${exemploProjUrl.value}"]` },
            { param: 'max_depth', desc: 'Profundidade maxima do crawl.', value: '5' },
            { param: 'max_pages', desc: 'Quantidade maxima de paginas a processar.', value: '500' },
            { param: 'include_images', desc: 'Inclui sitemap de imagens.', value: 'true' },
            { param: 'include_videos', desc: 'Inclui sitemap de videos.', value: 'true' },
            { param: 'massive_processing', desc: 'Ativa o modo massivo de processamento.', value: 'true' },
            { param: 'output_directory', desc: 'Diretorio de saida opcional.', value: outputDirectory.value },
        ],
        responseParams: [
            { param: 'job_id', desc: 'Identificador do job criado.', value: exemploJobId },
            { param: 'status', desc: 'Status inicial do job.', value: 'queued' },
            { param: 'message', desc: 'Mensagem de retorno da API.', value: 'Job criado e sera processado em breve' },
            { param: 'created_at', desc: 'Data de criacao do job.', value: '2026-03-27T11:00:23Z' },
            { param: 'estimated_duration_seconds', desc: 'Estimativa opcional de duracao.', value: '300' },
        ],
    },
    {
        id: 'get_status',
        titulo: 'GET /sitemaps/{job_id}',
        requestParams: [
            { param: 'job_id', desc: 'ID do job retornado na criacao.', value: exemploJobId },
            { param: 'Authorization (recommended)', desc: 'Use sua API Key para manter o fluxo autenticado.', value: authorizationHeader.value },
        ],
        responseParams: [
            { param: 'job_id', desc: 'Identificador do job.', value: exemploJobId },
            { param: 'status', desc: 'queued, running, completed, failed ou cancelled.', value: 'running' },
            { param: 'progress', desc: 'Percentual de progresso do job.', value: '45.5' },
            { param: 'message', desc: 'Mensagem atual do processamento.', value: 'Crawling em progresso' },
            {
                param: 'result',
                desc: 'Dados finais quando o job terminar.',
                value: null,
                json: `{\n  "job_id": "${exemploJobId}",\n  "status": "completed",\n  "progress": 100,\n  "message": "Processamento concluido",\n  "phase": "done",\n  "urls_found": 1250,\n  "urls_crawled": 568,\n  "images_found": 234,\n  "videos_found": 12,\n  "result": {\n    "main_sitemap_path": "${outputDirectory.value}/sitemap.xml",\n    "image_sitemap_path": "${outputDirectory.value}/sitemap_images.xml"\n  }\n}`,
            },
        ],
    },
    {
        id: 'list_artifacts',
        titulo: 'GET /sitemaps/{job_id}/artifacts',
        requestParams: [
            { param: 'Authorization', desc: 'Header HTTP com sua API Key no formato Bearer.', value: authorizationHeader.value },
            { param: 'job_id', desc: 'ID do job concluido.', value: exemploJobId },
        ],
        responseParams: [
            {
                param: 'artifacts',
                desc: 'Lista de arquivos gerados.',
                value: null,
                json: `{\n  "artifacts": [\n    {\n      "name": "sitemap.xml",\n      "type": "main",\n      "size_bytes": 245760,\n      "created_at": "2026-03-27T11:05:00Z",\n      "download_url": "/api/v1/sitemaps/${exemploJobId}/artifacts/sitemap.xml"\n    },\n    {\n      "name": "sitemap_images.xml",\n      "type": "images",\n      "size_bytes": 98211,\n      "created_at": "2026-03-27T11:05:00Z",\n      "download_url": "/api/v1/sitemaps/${exemploJobId}/artifacts/sitemap_images.xml"\n    }\n  ]\n}`,
            },
        ],
    },
    {
        id: 'download_artifact',
        titulo: 'GET /sitemaps/{job_id}/artifacts/{name}',
        isRaw: true,
        requestParams: [
            { param: 'Authorization', desc: 'Header HTTP com sua API Key no formato Bearer.', value: authorizationHeader.value },
            { param: 'job_id', desc: 'ID do job concluido.', value: exemploJobId },
            { param: 'name', desc: 'Nome do arquivo retornado pela listagem de artefatos.', value: 'sitemap.xml' },
        ],
        responseParams: [],
    },
    {
        id: 'cancel_job',
        titulo: 'POST /sitemaps/{job_id}/cancel',
        requestParams: [
            { param: 'Authorization', desc: 'Header HTTP com sua API Key no formato Bearer.', value: authorizationHeader.value },
            { param: 'job_id', desc: 'ID de um job queued ou running.', value: exemploJobId },
        ],
        responseParams: [
            { param: 'message', desc: 'Mensagem de cancelamento.', value: 'Job cancelado com sucesso' },
            { param: 'job_id', desc: 'Identificador do job cancelado.', value: exemploJobId },
            { param: 'cancelled_at', desc: 'Data de cancelamento.', value: '2026-03-27T11:03:10Z' },
        ],
    },
]);

const resetarChave = () => {
    if (!confirm('Isso invalidara sua chave atual. Continuar?')) return;

    resetando.value = true;
    router.post(route('account.api.reset-key'), {}, {
        preserveScroll: true,
        onFinish: () => { resetando.value = false; },
        onSuccess: () => {
            router.reload({ only: ['apiKey'] });
        },
    });
};

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
                    <div class="text-center py-4 border-b border-gray-200">
                        <h1 class="text-lg font-bold text-gray-700 uppercase tracking-wide">{{ appName }} API</h1>
                    </div>

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
                                Referencia REST
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
                                Setup API
                            </button>
                        </nav>
                    </div>

                    <div v-show="activeTab === 'reference'" class="p-6">
                        <p class="text-sm text-gray-700 mb-3">
                            A API atual do {{ appName }} e REST e orientada a jobs. Voce autentica com sua API Key em
                            <span class="font-mono">Authorization: Bearer ...</span>, cria um job de sitemap, consulta o status,
                            lista os artefatos gerados e baixa os arquivos finais.
                        </p>

                        <div class="mb-6 rounded border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600 space-y-1">
                            <p><span class="font-semibold text-gray-700">Base URL:</span> <span class="font-mono">{{ endpointBase }}</span></p>
                            <p><span class="font-semibold text-gray-700">Auth:</span> <span class="font-mono">Authorization: {{ authorizationHeader }}</span></p>
                            <p><span class="font-semibold text-gray-700">Formato:</span> JSON para criacao e cancelamento, JSON para status/listagem e download binario nos artefatos.</p>
                        </div>

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

                        <div v-if="projetos.length > 0" class="mb-6 p-3 border border-gray-200 rounded bg-gray-50 text-sm">
                            <p class="text-gray-600 mb-2">Selecione um projeto para preencher exemplos de URL e diretorio de saida.</p>
                            <select
                                v-model="selectedProjeto"
                                class="border border-gray-300 rounded px-3 py-1.5 text-sm w-72 focus:outline-none focus:ring-1 focus:ring-primary-400"
                            >
                                <option value="">Selecione um site</option>
                                <option v-for="p in projetos" :key="p.id" :value="p.id">{{ p.url }}</option>
                            </select>
                        </div>

                        <div class="space-y-0">
                            <div v-for="ep in endpoints" :key="ep.id" :id="ep.id" class="border border-gray-200 rounded mb-4">
                                <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b border-gray-200 gap-4">
                                    <span class="text-sm font-semibold text-gray-700">{{ ep.titulo }}</span>
                                    <span class="text-[11px] font-mono text-gray-500">{{ endpointBase }}</span>
                                </div>

                                <div class="border-b border-gray-100">
                                    <div class="px-4 py-2 bg-blue-50 text-xs text-gray-600 border-b border-blue-100 flex items-center gap-2">
                                        <span class="text-blue-500 font-bold">-&gt;</span>
                                        Parametros da requisicao
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="border-b border-gray-100 bg-gray-50">
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600 w-1/4">Nome</th>
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600 w-1/3">Descricao</th>
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600">Valor de exemplo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="p in ep.requestParams" :key="p.param" class="border-b border-gray-50 hover:bg-gray-50">
                                                    <td class="px-4 py-2 font-mono text-gray-700 align-top">{{ p.param }}</td>
                                                    <td class="px-4 py-2 text-gray-500 align-top">{{ p.desc }}</td>
                                                    <td class="px-4 py-2 text-gray-700 align-top break-all">
                                                        <span class="font-mono text-[10px] break-all">{{ p.value }}</span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div v-if="ep.isRaw" class="px-4 py-3 text-xs text-gray-600 bg-blue-50 flex items-center gap-2">
                                    <span class="text-blue-500 font-bold">&lt;-</span>
                                    A resposta sera o arquivo binario solicitado.
                                </div>
                                <div v-else-if="ep.responseParams && ep.responseParams.length">
                                    <div class="px-4 py-2 bg-blue-50 text-xs text-gray-600 border-b border-blue-100 flex items-center gap-2">
                                        <span class="text-blue-500 font-bold">&lt;-</span>
                                        Campos principais da resposta
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="border-b border-gray-100 bg-gray-50">
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600 w-1/4">Nome</th>
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600 w-1/3">Descricao</th>
                                                    <th class="text-left px-4 py-2 font-semibold text-gray-600">Valor de exemplo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="p in ep.responseParams" :key="p.param" class="border-b border-gray-50 hover:bg-gray-50">
                                                    <td class="px-4 py-2 font-mono text-gray-700 align-top">{{ p.param }}</td>
                                                    <td class="px-4 py-2 text-gray-500 align-top">{{ p.desc }}</td>
                                                    <td class="px-4 py-2 align-top">
                                                        <pre v-if="p.json" class="bg-blue-50 text-blue-800 rounded p-2 text-[10px] overflow-x-auto whitespace-pre-wrap font-mono leading-relaxed">{{ p.json }}</pre>
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

                    <div v-show="activeTab === 'setup'" class="p-6 space-y-6">
                        <div v-if="!podeAcessarApi" class="bg-amber-50 border border-amber-200 rounded p-4 text-sm text-amber-800">
                            Seu plano atual nao inclui acesso a API. Faca um upgrade para usar esta funcionalidade.
                        </div>

                        <div class="text-sm text-gray-700 space-y-1">
                            <p>As requisicoes externas devem enviar sua API Key no header Authorization, no formato Bearer <span class="font-mono">&lt;sua_api_key&gt;</span>, e usar payload JSON nos endpoints REST.</p>
                            <p>Use a aba de referencia para ver o contrato real de criacao de jobs, consulta de status, artefatos e cancelamento.</p>
                        </div>

                        <div class="text-center text-sm py-3 border border-gray-200 rounded bg-gray-50 text-gray-600">
                            O acesso externo a API so esta disponivel para contas com plano avancado e assinatura ativa.
                        </div>

                        <div class="border border-gray-200 rounded">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm text-gray-600">
                                Exemplos por projeto
                            </div>
                            <div class="px-4 py-3">
                                <select
                                    v-model="selectedProjeto"
                                    class="border border-gray-300 rounded px-3 py-1.5 text-sm w-80 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                >
                                    <option value="">Selecione um site</option>
                                    <option v-for="p in projetos" :key="p.id" :value="p.id">{{ p.url }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
                                Sua chave de API
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
                                            Clique aqui
                                        </button>
                                        se quiser redefinir sua chave de API.
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">Ao redefinir, a chave atual deixa de funcionar e todas as chamadas futuras devem usar a nova chave.</p>
                                </div>
                                <div v-else>
                                    <p class="text-sm text-gray-500 mb-3">Voce ainda nao possui uma chave de API.</p>
                                    <button
                                        @click="resetarChave"
                                        :disabled="resetando"
                                        class="bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold py-2 px-4 rounded uppercase tracking-wider transition disabled:opacity-50"
                                    >
                                        {{ resetando ? 'GERANDO...' : 'GERAR CHAVE DE API' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 text-sm font-semibold text-gray-700">
                                URL base da API {{ appName }}
                            </div>
                            <div class="px-4 py-4 space-y-2 text-sm text-gray-700">
                                <p>- use GET ou POST conforme o endpoint REST documentado na aba de referencia</p>
                                <p>- para endpoints com body, envie JSON com <strong>application/json</strong></p>
                                <p>- monte as rotas a partir desta base:</p>
                                <div class="border border-gray-200 rounded px-3 py-2 font-mono text-xs bg-gray-50 text-gray-600">
                                    {{ endpointUrl }}
                                </div>
                                <div class="border border-gray-200 rounded px-3 py-2 font-mono text-xs bg-gray-50 text-gray-600">
                                    Authorization: {{ authorizationHeader }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
