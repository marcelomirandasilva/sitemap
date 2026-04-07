<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    job: Object,
    logs: Array,
});

const formatData = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString() + ' ' + new Date(iso).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

const cancelarProcesso = () => {
    if (confirm('Tem certeza que deseja cancelar este job remoto?')) {
        router.delete(route('admin.jobs.cancel', props.job.id));
    }
};

const statusBadge = computed(() => {
    if (props.job.status === 'completed') return 'bg-green-50 text-green-700 border-green-200';
    if (props.job.status === 'running') return 'bg-sky-50 text-sky-700 border-sky-200';
    if (props.job.status === 'queued') return 'bg-slate-50 text-slate-700 border-slate-200';
    if (props.job.status === 'cancelled') return 'bg-amber-50 text-amber-700 border-amber-200';
    return 'bg-red-50 text-red-700 border-red-200';
});

const resumoJob = computed(() => ({
    id: props.job.id,
    external_job_id: props.job.external_job_id,
    status: props.job.status,
    progress: props.job.progress,
    pages_count: props.job.pages_count,
    urls_found: props.job.urls_found,
    urls_crawled: props.job.urls_crawled,
    urls_excluded: props.job.urls_excluded,
    images_count: props.job.images_count,
    videos_count: props.job.videos_count,
    message: props.job.message,
    started_at: props.job.started_at,
    completed_at: props.job.completed_at,
}));
</script>

<template>
    <Head :title="`Job #${job.id}`" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.jobs.index')" class="text-gray-400 hover:text-gray-600 transition shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </Link>
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-light text-gray-700 dark:text-gray-200">
                                Job <span class="font-bold text-primary-600 tracking-widest font-mono">#{{ String(job.id).padStart(5, '0') }}</span>
                            </h1>
                            <p class="text-sm text-gray-500 font-mono">{{ job.external_job_id }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span :class="['px-3 py-1 text-xs font-bold rounded border uppercase', statusBadge]">
                            {{ job.status }}
                        </span>
                        <button
                            v-if="['queued', 'running'].includes(job.status)"
                            @click="cancelarProcesso"
                            class="bg-danger-100 text-danger-700 border border-danger-200 px-4 py-2 rounded text-sm font-medium hover:bg-danger-200 transition shadow-sm uppercase tracking-wide"
                        >
                            Cancelar Job
                        </button>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-bold">Progresso</div>
                        <div class="text-3xl font-black text-primary-600 mt-2">{{ Math.floor(job.progress || 0) }}%</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-bold">URLs no sitemap</div>
                        <div class="text-3xl font-black text-primary-600 mt-2">{{ job.pages_count || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-bold">URLs descobertas</div>
                        <div class="text-3xl font-black text-primary-600 mt-2">{{ job.urls_found || 0 }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <div class="text-xs uppercase tracking-wide text-gray-500 font-bold">URLs excluídas</div>
                        <div class="text-3xl font-black text-primary-600 mt-2">{{ job.urls_excluded || 0 }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="space-y-6">
                        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                            <h3 class="font-bold text-gray-900 uppercase tracking-widest text-xs border-b border-gray-100 pb-2 mb-4">Contexto</h3>
                            <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide">Projeto</dt>
                                    <dd class="font-medium text-gray-900 mt-1">{{ job.projeto?.name || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide">URL</dt>
                                    <dd class="font-medium text-gray-900 mt-1 break-all">{{ job.projeto?.url || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide">Dono</dt>
                                    <dd class="font-medium text-gray-900 mt-1">{{ job.projeto?.user?.name || 'Sistema' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide">Iniciado em</dt>
                                    <dd class="font-mono text-gray-700 mt-1">{{ formatData(job.started_at || job.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide">Concluído em</dt>
                                    <dd class="font-mono text-gray-700 mt-1">{{ formatData(job.completed_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 text-[10px] uppercase font-bold tracking-wide">Mensagem</dt>
                                    <dd class="text-gray-700 mt-1">{{ job.message || '-' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                            <h3 class="font-bold text-gray-900 uppercase tracking-widest text-xs border-b border-gray-100 pb-2 mb-4">Métricas</h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">URLs crawled</dt><dd class="font-semibold text-gray-900">{{ job.urls_crawled || 0 }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">Imagens</dt><dd class="font-semibold text-gray-900">{{ job.images_count || 0 }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">Vídeos</dt><dd class="font-semibold text-gray-900">{{ job.videos_count || 0 }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">Artefatos</dt><dd class="font-semibold text-gray-900">{{ Array.isArray(job.artifacts) ? job.artifacts.length : 0 }}</dd></div>
                            </dl>
                        </div>
                    </div>

                    <div class="md:col-span-2 space-y-6">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">
                                Artefatos gerados
                            </h3>

                            <div v-if="Array.isArray(job.artifacts) && job.artifacts.length > 0" class="bg-[#0d1117] border border-gray-700 rounded-md overflow-hidden shadow-inner">
                                <div class="bg-[#161b22] px-4 py-2 text-[10px] uppercase tracking-wider font-mono text-gray-400 border-b border-gray-700">
                                    artifacts.json
                                </div>
                                <div class="p-4 max-h-[420px] overflow-y-auto w-full overflow-x-auto text-[13px] leading-relaxed">
                                    <pre class="text-[#a5d6ff] font-mono whitespace-pre">{{ JSON.stringify(job.artifacts, null, 2) }}</pre>
                                </div>
                            </div>

                            <div v-else class="text-center py-12 bg-gray-50 border-2 border-dashed border-gray-100 rounded-lg">
                                <p class="text-gray-500 text-sm font-medium">Este job ainda não possui artefatos persistidos.</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-base font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4">
                                Snapshot do job
                            </h3>

                            <div class="bg-[#0d1117] border border-gray-700 rounded-md overflow-hidden shadow-inner">
                                <div class="bg-[#161b22] px-4 py-2 text-[10px] uppercase tracking-wider font-mono text-gray-400 border-b border-gray-700">
                                    job.json
                                </div>
                                <div class="p-4 max-h-[420px] overflow-y-auto w-full overflow-x-auto text-[13px] leading-relaxed">
                                    <pre class="text-[#a5d6ff] font-mono whitespace-pre">{{ JSON.stringify(resumoJob, null, 2) }}</pre>
                                </div>
                            </div>
                        </div>

                        <div v-if="logs.length > 0" class="bg-[#fff1f2] border border-[#fecdd3] rounded-lg p-6 shadow-sm">
                            <h3 class="text-sm font-bold text-[#be123c] uppercase tracking-wider mb-4">
                                Logs relevantes
                            </h3>
                            <div class="space-y-3">
                                <div v-for="(log, idx) in logs" :key="idx" class="text-xs font-mono text-[#9f1239] bg-white px-4 py-3 rounded border border-[#ffe4e6] break-all leading-relaxed shadow-sm">
                                    {{ log.message }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
