<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    job: Object,
    logs: Array,
    paginas: Array,
});

const formatData = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString() + ' ' + new Date(iso).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
};

const cancelarProcesso = () => {
    if(confirm('Tem certeza que deseja forçar o encerramento deste rastreio logico?')) {
        router.delete(route('admin.jobs.cancel', props.job.id));
    }
};
</script>

<template>
    <Head :title="`Log de Crawler #${job.id}`" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.jobs.index')" class="text-gray-400 hover:text-gray-600 transition shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </Link>
                        <h1 class="text-2xl sm:text-3xl font-light text-gray-700 dark:text-gray-200">
                            Pipeline Crawler <span class="font-bold text-primary-600 tracking-widest font-mono">#{{ String(job.id).padStart(5, '0') }}</span>
                        </h1>
                    </div>
                    <button v-if="['queued', 'running'].includes(job.status)" @click="cancelarProcesso" class="bg-danger-100 text-danger-700 border border-danger-200 px-4 py-2 rounded text-sm font-medium hover:bg-danger-200 transition shadow-sm uppercase tracking-wide">
                        <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Abortar Job Imediatamente
                    </button>
                    <span v-else class="text-xs text-gray-500 px-3 py-1 bg-gray-100 rounded border font-mono">Status Terminal: {{ job.status }}</span>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Coluna Esquerda: Meta dados -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 uppercase tracking-widest text-xs border-b border-gray-100 dark:border-gray-700 pb-2 mb-4">Métricas Globais da Spider</h3>
                        
                        <dl class="space-y-5 text-sm">
                            <div>
                                <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg> Domínio (Host)</dt>
                                <dd class="font-semibold text-gray-900 dark:text-gray-100 mt-1 break-all bg-gray-50 dark:bg-gray-700/50 p-2 rounded border border-gray-100">{{ job.projeto?.url || 'URL Fantasma' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide">Proprietário / Conta</dt>
                                <dd class="font-medium text-gray-800 dark:text-gray-200 mt-0.5">{{ job.projeto?.user?.name || 'Sistema' }}</dd>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide">Quantidade de Páginas Escaneadas (Sucesso)</dt>
                                <dd class="font-black text-3xl text-primary-600 dark:text-primary-400 mt-1 leading-none">{{ job.pages_processed || 0 }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6 border-dashed">
                        <h3 class="font-bold text-gray-500 dark:text-gray-400 text-[10px] uppercase tracking-widest mb-3">Cronômetro / Assinaturas Temporais</h3>
                        <div class="space-y-2 text-xs text-gray-600 dark:text-gray-400 font-mono bg-white dark:bg-gray-800 p-3 rounded shadow-inner border border-gray-100">
                            <div class="flex justify-between border-b pb-1">
                                <span class="bg-gray-100 px-1 rounded">START:</span>
                                <span>{{ formatData(job.created_at) }}</span>
                            </div>
                            <div class="flex justify-between pt-1 text-primary-600">
                                <span class="bg-primary-50 px-1 rounded">PULSE:</span>
                                <span>{{ formatData(job.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coluna Direita: Arquivos JSON e Logs de Erro -->
                <div class="md:col-span-2 space-y-6">
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 pb-2">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white border-b border-gray-100 dark:border-gray-700 pb-3 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                            Artefato Mapeado em Banco (JSON Artifact)
                        </h3>
                        
                        <div v-if="paginas && paginas.length > 0" class="bg-[#0d1117] border border-gray-700 rounded-md overflow-hidden shadow-inner">
                            <div class="bg-[#161b22] px-4 py-2 text-[10px] uppercase tracking-wider font-mono text-gray-400 flex justify-between border-b border-gray-700">
                                <span class="flex items-center gap-1.5"><v-icon>📋</v-icon> memory_dump.json</span>
                                <span class="text-primary-400 font-bold">{{ paginas.length }} NÓS LOCAIS</span>
                            </div>
                            <div class="p-4 max-h-[600px] overflow-y-auto w-full overflow-x-auto text-[13px] leading-relaxed relative">
                                <!-- Linhas enumeradas visualmente usando CSS via classe custom se necessário, por ora pre tag resolve -->
                                <pre class="text-[#a5d6ff] font-mono whitespace-pre">{{ JSON.stringify(paginas, null, 2) }}</pre>
                            </div>
                        </div>

                        <div v-else class="text-center py-16 bg-gray-50 border-2 border-dashed border-gray-100 rounded-lg mt-2">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            <p class="text-gray-500 text-sm font-medium">Memória Mapeada Vazia.</p>
                            <p class="text-gray-400 text-xs mt-1">O rastreador ainda não inseriu links válidos no Payload, ou foi abortado prematuramente no boot.</p>
                        </div>
                    </div>

                    <!-- Logs Analíticos/Exception -->
                    <div v-if="logs.length > 0" class="bg-[#fff1f2] dark:bg-[#4c0519]/20 border border-[#fecdd3] dark:border-[#881337] rounded-lg p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-[#be123c] dark:text-[#fb7185] uppercase tracking-wider mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Pânico do Worker (Exceptions & Logs)
                        </h3>
                        <div class="space-y-3">
                            <div v-for="(log, idx) in logs" :key="idx" class="text-xs font-mono text-[#9f1239] dark:text-[#fda4af] bg-white dark:bg-[#4c0519] px-4 py-3 rounded border border-[#ffe4e6] dark:border-[#881337] break-all leading-relaxed shadow-sm">
                                {{ log.message }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </AppLayout>
</template>
