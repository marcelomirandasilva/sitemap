<script setup>
import { CheckIcon } from '@heroicons/vue/20/solid';
import { computed } from 'vue';

const props = defineProps({
    plan: Object,
    active: Boolean,
    billingCycle: {
        type: String,
        default: 'monthly'
    },
    isCancelled: Boolean,
    onGracePeriod: Boolean,
    endsAt: String,
    currentPriceId: String
});

const isPendingCancellation = computed(() => {
    if (!props.onGracePeriod) return false;
    
    // Este card é o plano que está sendo cancelado?
    return props.currentPriceId === props.plan.stripe_monthly_price_id || 
           props.currentPriceId === props.plan.stripe_yearly_price_id;
});

// Emits para o componente pai saber que o botão foi clicado
const emit = defineEmits(['subscribe']);

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
        ? '/ano' 
        : '/mês';
});

// Define qual ID do Stripe vamos usar (Apenas para validação visual, o envio é via evento)
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
            active ? 'border-primary-600 ring-1 ring-primary-600 bg-primary-50/10' : 'border-gray-200 bg-white',
            plan.has_advanced_features ? 'lg:z-10 lg:scale-105 shadow-md border-primary-200' : ''
        ]"
    >
        <div v-if="onGracePeriod && isPendingCancellation" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-orange-500 text-white px-4 py-1 rounded-full text-xs font-bold shadow-sm whitespace-nowrap">
            {{ $t('subscription.pending_cancellation') }}
        </div>

        <div class="mb-5">
            <h3 class="text-lg font-semibold leading-8 text-gray-900">{{ plan.name }}</h3>
            <p class="mt-4 text-base leading-6 text-gray-600">{{ plan.ideal_for || $t('subscription.ideal_for_scaling') }}</p>
            
            <p class="mt-6 flex items-baseline gap-x-1">
                <span class="text-4xl font-bold tracking-tight text-gray-900">
                    {{ formatCurrency(displayPrice) }}
                </span>
                <span class="text-sm font-semibold leading-6 text-gray-600">
                    {{ priceSuffix }}
                </span>
            </p>
            
            <p v-if="billingCycle === 'yearly' && (plan.stripe_monthly_price_id || plan.stripe_yearly_price_id)" class="mt-1 text-xs text-green-600 font-semibold">
                {{ $t('subscription.yearly_single_payment', { amount: formatCurrency(displayPrice) }) || 'Pagamento único de ' + formatCurrency(displayPrice) }}
            </p>
            <p v-if="onGracePeriod && isPendingCancellation" class="mt-1 text-xs text-orange-600 font-semibold">
                {{ $t('subscription.expires_on', { date: endsAt }) }}
            </p>
        </div>
        
        <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600 flex-1">
            <li class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-primary-600" aria-hidden="true" />
                {{ $t('pricing.table.update') }}: {{ plan.update_frequency || 'Manual' }}
            </li>
            <li class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-primary-600" aria-hidden="true" />
                {{ $t('subscription.features.max_pages', { count: plan.max_pages }) }}
            </li>
            <li class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-primary-600" aria-hidden="true" />
                {{ $t('subscription.features.max_projects', { count: plan.max_projects }) }}
            </li>
            <li v-if="plan.has_advanced_features" class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-primary-600" aria-hidden="true" />
                {{ $t('subscription.features.advanced') }}
            </li>
             <li v-if="plan.has_advanced_features" class="flex gap-x-3">
                <CheckIcon class="h-6 w-5 flex-none text-primary-600" aria-hidden="true" />
                {{ $t('subscription.features.api') }}
            </li>
        </ul>

        <button 
            v-if="(!targetStripePriceId || targetStripePriceId) && !active && !isPendingCancellation"
            @click="emit('subscribe')"
            class="mt-8 block w-full rounded-md px-3 py-2 text-center text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 bg-primary-50 text-primary-600 hover:bg-primary-100 transition-colors cursor-pointer"
        >
            {{ $t('subscription.select_plan') }}
        </button>
        
        <button 
            v-else
            disabled
            class="mt-8 block w-full rounded-md px-3 py-2 text-center text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 cursor-default"
            :class="[
                active 
                    ? 'bg-primary-600 text-white' 
                    : (isPendingCancellation ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-400')
            ]"
        >
            {{ active ? (isPendingCancellation ? $t('subscription.current_plan_cancelled') : $t('subscription.current_plan')) : (isPendingCancellation ? $t('subscription.cancelled_status') : $t('subscription.available')) }}
        </button>
    </div>
</template>