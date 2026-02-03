<script setup>
import { CheckIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    plan: Object,
    active: Boolean,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value / 100);
};
</script>

<template>
    <div 
        class="relative flex flex-col rounded-2xl border p-8 shadow-sm transition-all duration-200 hover:shadow-lg hover:-translate-y-1"
        :class="[
            active ? 'border-blue-600 ring-1 ring-blue-600 bg-blue-50/10' : 'border-gray-200 bg-white',
            plan.has_advanced_features ? 'lg:z-10 lg:scale-105 shadow-md border-blue-200' : ''
        ]"
    >
        <div class="mb-5">
            <h3 class="text-lg font-semibold leading-8 text-gray-900">{{ plan.name }}</h3>
            <p class="mt-4 text-base leading-6 text-gray-600">Ideal para escalar seus sitemaps.</p>
            <p class="mt-6 flex items-baseline gap-x-1">
                <span class="text-4xl font-bold tracking-tight text-gray-900">{{ formatCurrency(plan.price_monthly_brl) }}</span>
                <span class="text-sm font-semibold leading-6 text-gray-600">/mês</span>
            </p>
        </div>
        
        <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600 flex-1">
            <li class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                Até {{ plan.max_pages }} páginas
            </li>
            <li class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                Até {{ plan.max_projects }} projetos
            </li>
            <li v-if="plan.has_advanced_features" class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                Sitemap de Imagens e Vídeos
            </li>
             <li v-if="plan.has_advanced_features" class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                API de Automação
            </li>
        </ul>

        <a 
            :href="route('subscription.checkout', plan.stripe_id || 'price_default')" 
            class="mt-8 block w-full rounded-md px-3 py-2 text-center text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
            :class="[
                active 
                    ? 'bg-blue-600 text-white hover:bg-blue-500 focus-visible:outline-blue-600' 
                    : 'bg-indigo-50 text-indigo-600 hover:bg-indigo-100'
            ]"
        >
            {{ active ? 'Seu Plano Atual' : 'Assinar Agora' }}
        </a>
    </div>
</template>
