<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import StatusRastreador from '@/Components/Crawler/Status.vue';

const props = defineProps({
    projeto: {
        type: Object,
        required: true
    },
    userPlan: {
        type: Object,
        default: null
    }
});

const formataData = (data) => {
    if (!data) return 'Nunca';
    return new Date(data).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Conectado ao plano real do usuário
const badgePlano = computed(() => {
    if (props.userPlan) {
        return `${props.userPlan.name} `;
    }
    return 'FREE 500'; 
}); 
</script>

<template>
    <div class="bg-white rounded border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden flex flex-col">
        <!-- Header do Card -->
        <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-start">
            <div class="overflow-hidden">
                <h3 class="text-xl font-bold text-gray-800 truncate" :title="projeto.name || projeto.url">
                    <Link :href="route('projects.show', projeto.id)" class="hover:underline hover:text-primary-600 transition-colors">
                        {{ projeto.url.replace(/^https?:\/\//, '').replace(/\/$/, '') }}
                    </Link>
                </h3>
                <a :href="projeto.url" target="_blank" class="text-xs text-primary-500 hover:underline flex items-center gap-1 mt-1">
                    {{ projeto.url }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>
            
            <div class="flex flex-col items-end gap-2">
                <span class="px-2 py-1 bg-gray-200 text-gray-600 text-[10px] font-bold uppercase tracking-wider rounded-sm border border-gray-300">
                    {{ badgePlano }}
                </span>
                <Link :href="route('subscription.index')" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-premium-100 text-premium-700 border border-premium-200 hover:bg-premium-200 hover:text-premium-800 transition-colors">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $t('project.upgrade') }}
                </Link>
            </div>
        </div>

        <!-- Corpo do Card (Status do Rastreador) -->
        <div class="p-5 flex-1 flex flex-col justify-center">
            <StatusRastreador :projeto="projeto" :ultima-tarefa="projeto.ultimo_job" />
        </div>

        <!-- Footer do Card -->
        <div class="bg-gray-50 p-3 text-[11px] text-gray-500 flex justify-between items-center border-t border-gray-100">
            <div>
                <span class="font-semibold">{{ $t('project.created_at') }}</span> {{ formataData(projeto.created_at) }}
            </div>
            <div title="Páginas encontradas no último crawl">
                📄 {{ projeto.ultimo_job?.pages_count || 0 }} {{ $t('project.pages_count') }}
            </div>
        </div>
    </div>
</template>
