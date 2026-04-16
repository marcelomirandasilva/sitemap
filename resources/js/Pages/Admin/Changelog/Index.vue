<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import NavegacaoGestao from '@/Components/Admin/NavegacaoGestao.vue';

defineProps({
    registros: {
        type: Array,
        default: () => [],
    },
});

const formatarData = (dataIso) => {
    if (!dataIso) {
        return '-';
    }

    return new Date(`${dataIso}T00:00:00`).toLocaleDateString('pt-BR');
};

const remover = (id) => {
    if (confirm('Deseja realmente excluir este registro do changelog?')) {
        router.delete(route('admin.changelog.destroy', id));
    }
};
</script>

<template>
    <Head title="Gerenciar Changelog" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z"></path></svg>
                        <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">Gerenciamento do Changelog</h1>
                    </div>
                    <Link :href="route('admin.changelog.create')">
                        <PrimaryButton>+ Novo Registro</PrimaryButton>
                    </Link>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <NavegacaoGestao />

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-100 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-4">Versao</th>
                                    <th class="px-6 py-4">Data</th>
                                    <th class="px-6 py-4">Categoria</th>
                                    <th class="px-6 py-4">Titulo</th>
                                    <th class="px-6 py-4">Ordem</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Acoes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="registro in registros" :key="registro.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">v{{ registro.versao }}</td>
                                    <td class="px-6 py-4">{{ formatarData(registro.data_lancamento) }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-gray-900 dark:text-white">{{ registro.categoria_pt }}</div>
                                        <div class="text-xs text-gray-500">{{ registro.categoria_en }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ registro.titulo_pt }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-[380px]">{{ registro.titulo_en }}</div>
                                    </td>
                                    <td class="px-6 py-4">{{ registro.ordem_exibicao }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold border',
                                            registro.publicado
                                                ? 'bg-green-50 text-green-700 border-green-200'
                                                : 'bg-amber-50 text-amber-700 border-amber-200'
                                        ]">
                                            {{ registro.publicado ? 'Publicado' : 'Rascunho' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-3">
                                        <Link :href="route('admin.changelog.edit', registro.id)" class="inline-flex items-center text-primary-600 hover:text-primary-900 font-medium">
                                            Editar
                                        </Link>
                                        <button @click="remover(registro.id)" class="text-danger-600 hover:text-danger-900 font-medium">
                                            Excluir
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="registros.length === 0">
                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                        Nenhum registro de changelog cadastrado.
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
