<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { trans as t } from 'laravel-vue-i18n';

const props = defineProps({
    projeto: {
        type: Object,
        required: true
    },
    ultimaTarefa: {
        type: Object,
        default: null
    }
});

const tarefa = ref(props.ultimaTarefa);
const enquete = ref(null);
const iniciando = ref(false);

const corStatusPonto = computed(() => {
    switch (tarefa.value?.status) {
        case 'completed': return 'bg-green-500';
        case 'failed': return 'bg-red-500';
        case 'running': return 'bg-blue-500 animate-pulse';
        case 'queued': return 'bg-yellow-500';
        default: return 'bg-gray-300';
    }
});

const rotuloStatus = computed(() => {
    switch (tarefa.value?.status) {
        case 'completed': return t('crawler.status.completed');
        case 'failed': return t('crawler.status.failed');
        case 'running': return t('crawler.status.running');
        case 'queued': return t('crawler.status.queued');
        default: return t('crawler.status.waiting');
    }
});

const iniciarRastreador = () => {
    iniciando.value = true;
    const formulario = useForm({});
    
    formulario.post(route('crawler.store', props.projeto.id), {
        onSuccess: () => {
             buscarStatus();
             iniciarEnquete();
        },
        onFinish: () => iniciando.value = false
    });
};

const buscarStatus = async () => {
    try {
        const resposta = await axios.get(route('crawler.status', props.projeto.id));
        tarefa.value = resposta.data;
        
        if (['completed', 'failed', 'cancelled'].includes(tarefa.value.status)) {
            pararEnquete();
        }
    } catch (erro) {
        console.error('Erro ao buscar status do rastreador:', erro);
        pararEnquete();
    }
};

const iniciarEnquete = () => {
    if (enquete.value) return;
    enquete.value = setInterval(buscarStatus, 3000);
};

const pararEnquete = () => {
    if (enquete.value) {
        clearInterval(enquete.value);
        enquete.value = null;
    }
};

onMounted(() => {
    if (tarefa.value && ['queued', 'running'].includes(tarefa.value.status)) {
        iniciarEnquete();
    }
});

onUnmounted(() => {
    pararEnquete();
});
</script>

<template>
    <div class="w-full">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span v-if="tarefa" :class="['w-2 h-2 rounded-full', corStatusPonto]"></span>
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                    {{ rotuloStatus }}
                </span>
            </div>
            
            <button 
                @click="iniciarRastreador"
                :disabled="iniciando || (tarefa && ['queued', 'running'].includes(tarefa.status))"
                class="px-3 py-1.5 bg-[#007da0] hover:bg-[#006480] text-white text-[11px] font-bold uppercase rounded shadow-sm disabled:opacity-50 transition flex items-center gap-1"
            >
                <svg v-if="iniciando" class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span v-if="iniciando">{{ $t('crawler.starting') }}</span>
                <span v-else-if="tarefa && ['queued', 'running'].includes(tarefa.status)">{{ $t('crawler.processing') }}</span>
                <span v-else>{{ $t('crawler.resume_button') }}</span>
            </button>
        </div>

        <div v-if="tarefa">
            <!-- Barra de Progresso Cleaner -->
            <div v-if="['running', 'queued'].includes(tarefa.status)" class="w-full bg-gray-100 rounded-full h-1.5 mt-2 mb-1 overflow-hidden">
                <div 
                    class="bg-blue-500 h-1.5 rounded-full transition-all duration-500 relative" 
                    :style="{ width: (tarefa.progress || 0) + '%' }"
                >
                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                </div>
            </div>
            <p v-if="['running', 'queued'].includes(tarefa.status)" class="text-[10px] text-right text-gray-400 font-mono">
                {{ tarefa.progress || 0 }}%
            </p>

            <!-- Links de Download -->
            <div v-if="tarefa.status === 'completed' && tarefa.artifacts" class="mt-3 grid grid-cols-2 gap-2">
                <div v-for="(artefato, index) in tarefa.artifacts" :key="index">
                    <a 
                        :href="artefato.download_url" 
                        target="_blank"
                        class="block w-full text-center px-3 py-2 bg-white border border-gray-300 rounded text-xs text-blue-600 hover:bg-blue-50 transition"
                    >
                        ⬇️ {{ artefato.name }}
                        <span class="block text-[10px] text-gray-400">
                            {{ (artefato.size_bytes / 1024).toFixed(1) }} KB
                        </span>
                    </a>
                </div>
            </div>

            <!-- Mensagem de Erro -->
            <div v-if="tarefa.status === 'failed'" class="mt-2 text-xs text-red-600 bg-red-50 p-2 rounded border border-red-100">
                {{ tarefa.message || 'Erro desconhecido ao processar sitemap.' }}
            </div>
        </div>
    </div>
</template>
