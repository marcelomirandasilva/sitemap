<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    plan: Object,
    priceId: String,
    currentSubscription: Object,
});

const form = useForm({
    price_id: props.priceId,
});

const submit = () => {
    form.post(route('subscription.swap'), {
        onFinish: () => form.reset(),
    });
};

const price = computed(() => {
    // Determina se é mensal ou anual baseado no ID
    if (props.priceId === props.plan.stripe_yearly_price_id) {
        return { val: props.plan.price_yearly_brl, label: '/ano' };
    }
    return { val: props.plan.price_monthly_brl, label: '/mês' };
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val / 100);
};
</script>

<template>
    <Head title="Confirmar Mudança de Plano" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Confirmar Assinatura</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">
                    
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900">Confirme sua Mudança de Plano</h3>
                        <p class="text-gray-600 mt-2">Você está migrando para o plano <strong>{{ plan.name }}</strong>.</p>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6 mb-8 border border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-gray-600">Novo Plano</span>
                            <span class="font-bold text-lg">{{ plan.name }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-gray-600">Preço</span>
                            <span class="font-bold text-xl text-green-600">
                                {{ formatCurrency(price.val) }} <span class="text-sm font-normal text-gray-500">{{ price.label }}</span>
                            </span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <span class="text-gray-600">Cobrança</span>
                            <span class="text-sm text-gray-500">A diferença será cobrada/creditada proporcionalmente no seu cartão.</span>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="flex justify-center flex-col gap-4">
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="form.processing">Processando...</span>
                            <span v-else>Confirmar e Alterar Plano</span>
                        </button>
                        
                        <a :href="route('subscription.index')" class="text-center text-sm text-gray-500 hover:text-gray-800 underline">
                            Cancelar e Voltar
                        </a>
                    </form>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
