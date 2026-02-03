<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import PricingCard from '@/Components/PricingCard.vue';
import { ref } from 'vue';

const props = defineProps({
    plans: Array,
    currentSubscription: Object,
});

// Estado do Ciclo de Cobrança ('monthly' ou 'yearly')
const billingCycle = ref('monthly');

// Removemos o Mock. Agora a verdade vem apenas do banco de dados via props.
const displayPlans = props.plans;

// Helper para verificar se o plano está ativo (independente se é mensal ou anual)
const isPlanActive = (plan) => {
    const currentPrice = props.currentSubscription?.stripe_price;
    if (!currentPrice) return false;
    return currentPrice === plan.stripe_monthly_price_id || currentPrice === plan.stripe_yearly_price_id;
};
</script>

<template>
    <Head :title="$t('subscription.title')" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $t('subscription.header_title') }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-10">
                     <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $t('subscription.hero_title') }}</h2>
                     <p class="mt-6 text-lg leading-8 text-gray-600">{{ $t('subscription.hero_subtitle') }}</p>
                </div>

                <div class="flex justify-center mb-12">
                    <div class="relative flex rounded-full bg-gray-100 p-1 ring-1 ring-gray-200">
                        <button 
                            type="button"
                            @click="billingCycle = 'monthly'"
                            :class="[
                                billingCycle === 'monthly' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-900',
                                'rounded-full px-6 py-2 text-sm font-semibold leading-5 transition-all duration-200'
                            ]"
                        >
                            {{ $t('subscription.monthly') || 'Mensal' }}
                        </button>
                        <button 
                            type="button"
                            @click="billingCycle = 'yearly'"
                            :class="[
                                billingCycle === 'yearly' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-900',
                                'relative rounded-full px-6 py-2 text-sm font-semibold leading-5 transition-all duration-200'
                            ]"
                        >
                            {{ $t('subscription.yearly') || 'Anual' }}
                            <span class="absolute -top-3 -right-6 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 ring-1 ring-inset ring-green-600/20">
                                {{ $t('subscription.save_badge') }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 gap-x-8">
                    <PricingCard 
                        v-for="plan in displayPlans" 
                        :key="plan.id" 
                        :plan="plan" 
                        :billing-cycle="billingCycle"
                        :active="isPlanActive(plan)"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>