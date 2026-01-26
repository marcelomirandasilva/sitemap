<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { trans as t } from 'laravel-vue-i18n';

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    latestJob: {
        type: Object,
        default: null
    }
});

const job = ref(props.latestJob);
const polling = ref(null);
const isStarting = ref(false);

const statusColorDot = computed(() => {
    switch (job.value?.status) {
        case 'completed': return 'bg-green-500';
        case 'failed': return 'bg-red-500';
        case 'running': return 'bg-blue-500 animate-pulse';
        case 'queued': return 'bg-yellow-500';
        default: return 'bg-gray-300';
    }
});

const statusLabel = computed(() => {
    switch (job.value?.status) {
        case 'completed': return t('crawler.status.completed');
        case 'failed': return t('crawler.status.failed');
        case 'running': return t('crawler.status.running');
        case 'queued': return t('crawler.status.queued');
        default: return t('crawler.status.waiting');
    }
});

const startCrawler = () => {
    isStarting.value = true;
    const form = useForm({});
    
    form.post(route('crawler.store', props.project.id), {
        onSuccess: () => {
             // O reload da página vai trazer o novo job via prop inicial, 
             // mas podemos forçar o refresh local se necessário.
             fetchStatus();
             startPolling();
        },
        onFinish: () => isStarting.value = false
    });
};

const fetchStatus = async () => {
    try {
        const response = await axios.get(route('crawler.status', props.project.id));
        job.value = response.data;
        
        if (['completed', 'failed', 'cancelled'].includes(job.value.status)) {
            stopPolling();
        }
    } catch (error) {
        console.error('Erro ao buscar status do crawler:', error);
        stopPolling();
    }
};

const startPolling = () => {
    if (polling.value) return;
    polling.value = setInterval(fetchStatus, 3000);
};

const stopPolling = () => {
    if (polling.value) {
        clearInterval(polling.value);
        polling.value = null;
    }
};

onMounted(() => {
    if (job.value && ['queued', 'running'].includes(job.value.status)) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
});
</script>

<template>
    <div class="w-full">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span v-if="job" :class="['w-2 h-2 rounded-full', statusColorDot]"></span>
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                    {{ statusLabel }}
                </span>
            </div>
            
            <button 
                @click="startCrawler"
                :disabled="isStarting || (job && ['queued', 'running'].includes(job.status))"
                class="px-3 py-1.5 bg-[#007da0] hover:bg-[#006480] text-white text-[11px] font-bold uppercase rounded shadow-sm disabled:opacity-50 transition flex items-center gap-1"
            >
                <svg v-if="isStarting" class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span v-if="isStarting">{{ $t('crawler.starting') }}</span>
                <span v-else-if="job && ['queued', 'running'].includes(job.status)">{{ $t('crawler.processing') }}</span>
                <span v-else>{{ $t('crawler.resume_button') }}</span>
            </button>
        </div>

        <div v-if="job">
            <!-- Barra de Progresso Cleaner -->
            <div v-if="['running', 'queued'].includes(job.status)" class="w-full bg-gray-100 rounded-full h-1.5 mt-2 mb-1 overflow-hidden">
                <div 
                    class="bg-blue-500 h-1.5 rounded-full transition-all duration-500 relative" 
                    :style="{ width: (job.progress || 0) + '%' }"
                >
                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                </div>
            </div>
            <p v-if="['running', 'queued'].includes(job.status)" class="text-[10px] text-right text-gray-400 font-mono">
                {{ job.progress || 0 }}%
            </p>

            <!-- Links de Download -->
            <div v-if="job.status === 'completed' && job.artifacts" class="mt-3 grid grid-cols-2 gap-2">
                <div v-for="(artifact, index) in job.artifacts" :key="index">
                    <a 
                        :href="artifact.download_url" 
                        target="_blank"
                        class="block w-full text-center px-3 py-2 bg-white border border-gray-300 rounded text-xs text-blue-600 hover:bg-blue-50 transition"
                    >
                        ⬇️ {{ artifact.name }}
                        <span class="block text-[10px] text-gray-400">
                            {{ (artifact.size_bytes / 1024).toFixed(1) }} KB
                        </span>
                    </a>
                </div>
            </div>

            <!-- Mensagem de Erro -->
            <div v-if="job.status === 'failed'" class="mt-2 text-xs text-red-600 bg-red-50 p-2 rounded border border-red-100">
                {{ job.message || 'Erro desconhecido ao processar sitemap.' }}
            </div>
        </div>
    </div>
</template>
