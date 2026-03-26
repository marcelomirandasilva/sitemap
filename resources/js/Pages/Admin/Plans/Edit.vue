<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    plano: Object,
});

const isEditing = !!props.plano.id;

const form = useForm({
    name: props.plano.name || '',
    slug: props.plano.slug || '',
    ideal_for: props.plano.ideal_for || '',
    update_frequency: props.plano.update_frequency || '',
    max_projects: props.plano.max_projects ?? 10,
    max_pages: props.plano.max_pages ?? 500,
    has_advanced_features: !!props.plano.has_advanced_features,
    permite_imagens: !!props.plano.permite_imagens,
    permite_videos: !!props.plano.permite_videos,
    stripe_monthly_price_id: props.plano.stripe_monthly_price_id || '',
    stripe_yearly_price_id: props.plano.stripe_yearly_price_id || '',
    price_monthly_brl: props.plano.price_monthly_brl || null,
    price_yearly_brl: props.plano.price_yearly_brl || null,
    price_monthly_usd: props.plano.price_monthly_usd || null,
    price_yearly_usd: props.plano.price_yearly_usd || null,
});

const salvar = () => {
    if (isEditing) {
        form.put(route('admin.plans.update', props.plano.id));
    } else {
        form.post(route('admin.plans.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Editar Plano' : 'Novo Plano'" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-3">
                    <Link :href="route('admin.plans.index')" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </Link>
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        {{ isEditing ? 'Editar Configurações do Plano' : 'Criar Novo Plano' }}
                    </h1>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <form @submit.prevent="salvar" class="space-y-6">
                    
                    <!-- Configurações Gerais -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2 mb-4">Informações Gerais</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome do Plano</label>
                                <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug Único</label>
                                <input v-model="form.slug" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.slug" class="mt-2" />
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ideal para (Ex: Times grandes)</label>
                                <input v-model="form.ideal_for" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.ideal_for" class="mt-2" />
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frequência de Atualização Automática</label>
                                <select v-model="form.update_frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <option value="" disabled>Selecione uma Frequência</option>
                                    <option value="diario">Diária (A cada 24h)</option>
                                    <option value="semanal">Semanal (A cada 7 dias)</option>
                                    <option value="quinzenal">Quinzenal (A cada 15 dias)</option>
                                    <option value="mensal">Mensal (A cada 30 dias)</option>
                                    <option value="anual">Anual (A cada 365 dias)</option>
                                    <option value="manual">Sob Demanda (Apenas Manual)</option>
                                </select>
                                <InputError :message="form.errors.update_frequency" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Limites Operacionais -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2 mb-4">Limites Operacionais</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Máximo de Projetos (Sites) <span class="text-xs text-gray-400 font-normal">(-1 para infinito)</span></label>
                                <input v-model="form.max_projects" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.max_projects" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Máximo de Páginas <span class="text-xs text-gray-400 font-normal">(-1 para infinito)</span></label>
                                <input v-model="form.max_pages" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.max_pages" class="mt-2" />
                            </div>
                            <div class="col-span-1 md:col-span-2 pt-4 grid grid-cols-1 md:grid-cols-3 gap-4 border-t dark:border-gray-700">
                                <label class="flex items-start">
                                    <input v-model="form.has_advanced_features" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recursos Avançados</span>
                                    </div>
                                </label>
                                <label class="flex items-start">
                                    <input v-model="form.permite_imagens" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permite Imagens</span>
                                    </div>
                                </label>
                                <label class="flex items-start">
                                    <input v-model="form.permite_videos" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permite Vídeos</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Stripe e Preços em Centavos -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2 mb-4">Integração Stripe e Preços (Definido em Centavos Integrais - Ex: R$ 5,00 = 500)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stripe Price ID (Mensal)</label>
                                <input v-model="form.stripe_monthly_price_id" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" placeholder="price_xxxx">
                                <InputError :message="form.errors.stripe_monthly_price_id" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stripe Price ID (Anual)</label>
                                <input v-model="form.stripe_yearly_price_id" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" placeholder="price_yyyy">
                                <InputError :message="form.errors.stripe_yearly_price_id" class="mt-2" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Mensal (BRL Centavos)</label>
                                <input v-model="form.price_monthly_brl" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.price_monthly_brl" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Anual (BRL Centavos)</label>
                                <input v-model="form.price_yearly_brl" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.price_yearly_brl" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Mensal (USD Cents)</label>
                                <input v-model="form.price_monthly_usd" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.price_monthly_usd" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Anual (USD Cents)</label>
                                <input v-model="form.price_yearly_usd" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.price_yearly_usd" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-row-reverse items-center gap-4 mb-20 px-2 justify-start sm:justify-start lg:justify-start">
                        <PrimaryButton :processing="form.processing" type="submit">
                            {{ isEditing ? 'Gravar Alterações' : 'Criar Plano Definitivo' }}
                        </PrimaryButton>
                        <Link :href="route('admin.plans.index')" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition">Cancelar Operação</Link>
                    </div>

                </form>
            </div>
        </template>
    </AppLayout>
</template>
