<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import NavegacaoGestao from '@/Components/Admin/NavegacaoGestao.vue';

defineProps({
    stats: {
        type: Object,
        required: true,
    }
});
</script>

<template>
    <Head title="Administracao Geral" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex items-center justify-center gap-3">
                    <svg class="w-8 h-8 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        Genmap <span class="font-bold text-primary-600">Gestão</span>
                    </h1>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <NavegacaoGestao />

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center">
                        <div class="p-3 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-300 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Usuarios</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.totalUsers }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center">
                        <div class="p-3 rounded-full bg-accent-100 dark:bg-accent-900 text-accent-600 dark:text-accent-300 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Projetos Ativos</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.activeProjects }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center">
                        <div class="p-3 rounded-full bg-warning-100 dark:bg-warning-900 text-warning-600 dark:text-warning-300 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Jobs em Processamento</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.runningJobs }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center">
                        <div class="p-3 rounded-full bg-success-100 dark:bg-success-900 text-success-600 dark:text-success-300 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Inscricoes Livres (7D)</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.newUsersLastWeek }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
