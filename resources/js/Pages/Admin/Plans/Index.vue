<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    planos: Array,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
};

const destroy = (id) => {
    if (confirm('Atenção: Deletar um plano que possui usuários atrelados pode corromper os limites do app no middleware. Confirma?')) {
        router.delete(route('admin.plans.destroy', id));
    }
};
</script>

<template>
    <Head title="Gerenciar Planos" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                            Gerenciamento de Planos
                        </h1>
                    </div>
                    <Link :href="route('admin.plans.create')">
                        <PrimaryButton>
                            + Criar Novo Plano
                        </PrimaryButton>
                    </Link>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Navigation Tabs Administration -->
                <div class="flex space-x-4 mb-8 border-b border-gray-200 dark:border-gray-700 pb-2 overflow-x-auto">
                    <Link :href="route('admin.dashboard')" :class="route().current('admin.dashboard') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Painel Executivo</Link>
                    <Link :href="route('admin.users.index')" :class="route().current('admin.users.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Usuários</Link>
                    <Link :href="route('admin.plans.index')" :class="route().current('admin.plans.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Planos</Link>
                    <Link :href="route('admin.tickets.index')" :class="route().current('admin.tickets.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Suporte (Tickets)</Link>
                    <Link :href="route('admin.jobs.index')" :class="route().current('admin.jobs.*') ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2' : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium'">Motor Crawler</Link>
                </div>

                <!-- Tabela de Planos -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-100 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-4">Nome do Plano / Slug</th>
                                    <th class="px-6 py-4">Desbloqueios (Projetos x Pags)</th>
                                    <th class="px-6 py-4">Billing Mensal (BRL)</th>
                                    <th class="px-6 py-4 text-center">Avançado</th>
                                    <th class="px-6 py-4 px-3 text-right">Controles</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="plano in planos" :key="plano.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4 text-sm">
                                        <div class="font-bold text-gray-900 dark:text-white flex items-center">
                                            {{ plano.name }}
                                            <span v-if="plano.ideal_for" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                                {{ plano.ideal_for }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5 font-mono">{{ plano.slug }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm whitespace-nowrap">
                                        <div class="text-gray-900 dark:text-gray-100 font-medium">{{ plano.max_projects === -1 ? 'Ilimitado' : plano.max_projects }} Projetos</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ plano.max_pages === -1 ? 'Ilimitado' : plano.max_pages }} Páginas Motores/cada</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">
                                        <span v-if="plano.price_monthly_brl > 0" class="text-emerald-700 bg-emerald-50 px-2 py-1 rounded dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            R$ {{ (plano.price_monthly_brl / 100).toFixed(2).replace('.', ',') }} <span class="text-xs font-normal opacity-70">/mês</span>
                                        </span>
                                        <span v-else class="text-gray-500 bg-gray-100 px-2 py-1 rounded dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                            Gratuito (Free)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-center">
                                        <span v-if="plano.has_advanced_features" class="inline-flex items-center text-success-600 bg-success-50 px-2 py-0.5 rounded text-xs border border-success-200 dark:bg-success-900/30 dark:text-success-400 dark:border-success-800">
                                            Sim
                                        </span>
                                        <span v-else class="inline-flex items-center text-gray-500 bg-gray-100 px-2 py-0.5 rounded text-xs border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">Não</span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <Link :href="route('admin.plans.edit', plano.id)" class="inline-flex items-center text-primary-600 hover:text-primary-900 font-medium">
                                            Editar Limites
                                        </Link>
                                        <button @click="destroy(plano.id)" class="text-danger-600 hover:text-danger-900 font-medium">
                                            Lixeira
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="planos.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        Nenhum plano configurado. Crie um novo para popular a Landing Page.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </template>
    </AppLayout>
</template>
