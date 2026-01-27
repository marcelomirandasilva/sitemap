<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as t } from 'laravel-vue-i18n';
import StatusRastreador from '@/Components/Crawler/StatusRastreador.vue';

const props = defineProps({
    projeto: {
        type: Object,
        required: true
    }
});

const formataData = (data) => {
    if (!data) return '';
    return new Date(data).toLocaleDateString(t('locale') === 'pt' ? 'pt-BR' : 'en-US', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const badgePlano = computed(() => 'FREE 500'); 
</script>

<template>
    <tr class="hover:bg-gray-50 transition-colors border-b border-gray-100 last:border-0">
        <!-- Status Column -->
        <td class="p-4 align-top w-48">
            <div class="flex flex-col gap-2 items-start">
                <span class="px-2 py-1 bg-white text-gray-600 text-[10px] font-bold uppercase tracking-wider rounded-sm border border-gray-300 shadow-sm whitespace-nowrap">
                    {{ badgePlano }}
                    <span class="text-green-600 ml-1 simulate_arrow">{{ $t('project.upgrade') }}</span>
                </span>
            </div>
        </td>

        <!-- Domain Column -->
        <td class="p-4 align-top">
            <Link :href="route('projects.show', projeto.id)" class="text-base font-normal text-[#c0392b] hover:underline block truncate">
                {{ projeto.url.replace(/^https?:\/\//, '').replace(/\/$/, '') }}
            </Link>
        </td>

        <!-- Title Column -->
        <td class="p-4 align-top text-sm text-gray-500">
            {{ projeto.name || '-' }}
        </td>

        <!-- Updated Column -->
        <td class="p-4 align-top w-1/3">
            <div class="flex flex-col gap-1">
                <div class="text-xs text-gray-500 mb-1" v-if="projeto.latest_job">
                   {{ projeto.latest_job.status === 'completed' ? formataData(projeto.latest_job.updated_at) : '' }}
                   <span v-if="projeto.latest_job.status === 'completed'">, {{ projeto.latest_job.pages_count }} {{ $t('project.pages_count') }}</span>
                   <span v-else>
                       Sitemap has not been created yet, please wait...
                   </span>
                </div>
                
                <!-- Crawler Status Compacto -->
                <StatusRastreador :projeto="projeto" :ultima-tarefa="projeto.latest_job" />
            </div>
        </td>
    </tr>
</template>

<style scoped>
/* Simulando o ícone de seta dupla verde da imagem */
.simulate_arrow::before {
    content: "︽"; 
    font-size: 10px;
    margin-right: 2px;
}
</style>
