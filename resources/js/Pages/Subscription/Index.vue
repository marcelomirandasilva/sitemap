<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import PricingCard from '@/Components/PricingCard.vue';

const props = defineProps({
    plans: Array,
    currentSubscription: Object,
});

// Mock plans if empty (for dev preview)
const displayPlans = props.plans.length ? props.plans : [
    { id: 1, name: 'Basic', price_monthly_brl: 2990, max_pages: 1000, max_projects: 1, stripe_id: 'price_basic' },
    { id: 2, name: 'Pro', price_monthly_brl: 8990, max_pages: 10000, max_projects: 10, has_advanced_features: true, stripe_id: 'price_pro' },
    { id: 3, name: 'Enterprise', price_monthly_brl: 29900, max_pages: 100000, max_projects: 50, has_advanced_features: true, stripe_id: 'price_enterprise' },
];

</script>

<template>
    <Head title="Planos e Assinatura" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Planos & Preços</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                     <h2 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Escolha o plano ideal</h2>
                     <p class="mt-6 text-lg leading-8 text-gray-600">Comece pequeno e escale conforme sua necessidade. Cancele quando quiser.</p>
                </div>

                <div class="grid grid-cols-1 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 gap-x-8">
                    <PricingCard 
                        v-for="plan in displayPlans" 
                        :key="plan.id" 
                        :plan="plan" 
                        :active="currentSubscription?.stripe_price === plan.stripe_id"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
