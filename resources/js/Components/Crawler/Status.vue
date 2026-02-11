<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
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
    },
    simple: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:job']);

const tarefa = ref(props.ultimaTarefa);
const enquete = ref(null);
const iniciando = ref(false);
const jobIdMonitorado = ref(null); // ID do job que iniciamos manualmente

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

const mensagemErroSanitizada = computed(() => {
    const msg = tarefa.value?.message || '';
    // Palavras-chave técnicas para ocultar
    const techTerms = ['cURL', 'SQL', 'Exception', 'Stack trace', 'line', 'http', 'failed to connect'];
    
    if (techTerms.some(term => msg.toLowerCase().includes(term.toLowerCase()))) {
        return t('crawler.error_generic') || 'Ocorreu uma falha técnica. Detalhes foram registrados no log.';
    }
    
    return msg || t('crawler.error_unknown') || 'Erro desconhecido.';
});

const iniciarRastreador = async () => {
    if (iniciando.value) return;
    
    iniciando.value = true;
    try {
        const res = await axios.post(route('crawler.store', props.projeto.id));
        
        // Captura o ID do novo job
        const newJobId = res.data.external_job_id || res.data.job_id;
        if (newJobId) {
            jobIdMonitorado.value = newJobId;
            // UI Otimista: Já mostra como Queued imediatamente
            tarefa.value = { 
                ...tarefa.value, 
                status: 'queued', 
                progress: 0,
                external_job_id: newJobId 
            };
        }

        iniciarEnquete();
    } catch (error) {
        console.error('Erro ao iniciar rastreador:', error);
        alert('Falha ao iniciar o crawler. Verifique o console.');
    } finally {
        iniciando.value = false;
    }
};

const agendarProximaBusca = () => {
    if (enquete.value === null) return;
    enquete.value = setTimeout(buscarStatus, 3000);
};

const buscarStatus = async () => {
    try {
        const resposta = await axios.get(route('crawler.show', props.projeto.id) + '?t=' + new Date().getTime());
        const jobRecebido = resposta.data;

        // VALIDAÇÃO DE CORRIDA:
        // Se estamos monitorando um job específico (pq acabamos de iniciar), 
        // e a API retorna um job diferente OU um job 'completed' antigo... ignoramos por enquanto.
        if (jobIdMonitorado.value) {
            // Se o ID não bate, pode ser cache ou delay do banco. Ignora e tenta de novo.
            if (jobRecebido.external_job_id && jobRecebido.external_job_id !== jobIdMonitorado.value) {
                console.warn('Status.vue: Recebido job antigo, aguardando propagação...');
                agendarProximaBusca();
                return;
            }
        }

        // Se mudou algo, atualiza
        if (JSON.stringify(tarefa.value) !== JSON.stringify(jobRecebido)) {
            tarefa.value = jobRecebido;
            emit('update:job', tarefa.value); // IMPORTANTE: Avisa o pai (Index.vue)
            console.log('Status.vue: Atualizado para', tarefa.value?.status, tarefa.value?.progress);
        }
        
        // Lógica de parada
        if (tarefa.value && ['completed', 'failed', 'cancelled'].includes(tarefa.value.status)) {
            jobIdMonitorado.value = null; // Limpa monitoramento específico
            pararEnquete();
        } else if (['queued', 'running'].includes(tarefa.value?.status)) {
            agendarProximaBusca();
        }
    } catch (erro) {
        console.error('Erro ao buscar status:', erro);
        if (enquete.value !== null) {
            enquete.value = setTimeout(buscarStatus, 5000);
        }
    }
};

const iniciarEnquete = () => {
    if (enquete.value) return; 
    enquete.value = true;
    buscarStatus();
};

const pararEnquete = () => {
    if (enquete.value) clearTimeout(enquete.value);
    enquete.value = null;
};

// Se a prop mudar (ex: via Modal), atualizamos o local
watch(() => props.ultimaTarefa, (novaTarefa) => {
    if (novaTarefa && JSON.stringify(tarefa.value) !== JSON.stringify(novaTarefa)) {
        tarefa.value = novaTarefa;
        if (['queued', 'running'].includes(novaTarefa.status)) {
            iniciarEnquete();
        }
    }
}, { deep: true });

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
    <div v-if="simple">
        <span 
            :class="['px-2 py-1 text-[10px] font-bold uppercase tracking-wider rounded-sm shadow-sm whitespace-nowrap text-white', corStatusPonto]"
        >
            {{ rotuloStatus }}
        </span>
    </div>

    <div v-else class="w-full">
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
                {{ Math.round(tarefa.progress || 0) }}%
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
                        <span v-if="artefato.size_bytes > 0" class="block text-[10px] text-gray-400">
                            {{ (artefato.size_bytes / 1024).toFixed(1) }} KB
                        </span>
                        <span v-else class="block text-[10px] text-gray-400">
                            {{ $t('crawler.file_ready') }}
                        </span>
                    </a>
                </div>
            </div>

            <!-- Mensagem de Erro (Sanitizada no Frontend também) -->
            <div v-if="tarefa.status === 'failed'" class="mt-2 text-xs text-red-600 bg-red-50 p-2 rounded border border-red-100">
                {{ mensagemErroSanitizada }}
            </div>
        </div>
    </div>
</template>
