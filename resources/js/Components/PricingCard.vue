<script setup>
import { CheckIcon } from '@heroicons/vue/20/solid';
import { computed } from 'vue';

const props = defineProps({
    plan: Object,
    active: Boolean,
    billingCycle: {
        type: String,
        default: 'monthly'
    }
});

const formatCurrency = (value) => {
    if (!value) return 'R$ 0,00';
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value / 100);
};

// Define qual preço exibir na tela (Card)
const displayPrice = computed(() => {
    return props.billingCycle === 'yearly' 
        ? props.plan.price_yearly_brl 
        : props.plan.price_monthly_brl;
});

// Define qual sufixo mostrar (/mês ou /ano)
const priceSuffix = computed(() => {
    return props.billingCycle === 'yearly' 
        ? '/ano' // ou use $t('subscription.per_year')
        : '/mês'; // ou use $t('subscription.per_month')
});

// Define qual ID do Stripe enviar para o Checkout
const targetStripePriceId = computed(() => {
    return props.billingCycle === 'yearly'
        ? props.plan.stripe_yearly_price_id
        : props.plan.stripe_monthly_price_id;
});

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
            <p class="mt-4 text-base leading-6 text-gray-600">{{ $t('subscription.ideal_for_scaling') }}</p>
            
            <p class="mt-6 flex items-baseline gap-x-1">
                <span class="text-4xl font-bold tracking-tight text-gray-900">
                    {{ formatCurrency(displayPrice) }}
                </span>
                <span class="text-sm font-semibold leading-6 text-gray-600">
                    {{ priceSuffix }}
                </span>
            </p>
            
            <p v-if="billingCycle === 'yearly'" class="mt-1 text-xs text-green-600 font-semibold">
                Pagamento único de {{ formatCurrency(displayPrice) }}
            </p>
        </div>
        
        <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600 flex-1">
            <li class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                {{ $t('subscription.features.max_pages', { count: plan.max_pages }) }}
            </li>
            <li class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                {{ $t('subscription.features.max_projects', { count: plan.max_projects }) }}
            </li>
            <li v-if="plan.has_advanced_features" class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                {{ $t('subscription.features.advanced') }}
            </li>
             <li v-if="plan.has_advanced_features" class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-blue-600" aria-hidden="true" />
                {{ $t('subscription.features.api') }}
            </li>
        </ul>

        <a 
            v-if="targetStripePriceId && !active"
            :href="route('subscription.checkout', targetStripePriceId)" 
            class="mt-8 block w-full rounded-md px-3 py-2 text-center text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"
        >
            {{ $t('subscription.subscribe_now') }}
        </a>
        
        <button 
            v-else
            disabled
            class="mt-8 block w-full rounded-md px-3 py-2 text-center text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 cursor-default"
            :class="[
                active 
                    ? 'bg-blue-600 text-white' 
                    : 'bg-gray-100 text-gray-400'
            ]"
        >
            {{ active ? $t('subscription.current_plan') : $t('subscription.available') }}
        </button>
    </div>
</template>