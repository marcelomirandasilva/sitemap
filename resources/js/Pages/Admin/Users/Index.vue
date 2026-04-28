<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import NavegacaoGestao from '@/Components/Admin/NavegacaoGestao.vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const sort = ref({
    column: props.filters.sort_by || 'created_at',
    order: props.filters.sort_order || 'desc'
});

const updateQuery = () => {
    router.get(route('admin.users.index'), { 
        search: search.value,
        sort_by: sort.value.column,
        sort_order: sort.value.order
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

watch(search, (value) => {
    updateQuery();
});

const sortBy = (column) => {
    if (sort.value.column === column) {
        sort.value.order = sort.value.order === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value.column = column;
        sort.value.order = 'asc';
    }
    updateQuery();
};

const impersonate = (userId) => {
    if (confirm('Deseja realmente assumir o controle desta conta? Você sairá do modo Administrador.')) {
        router.post(route('admin.users.impersonate', userId));
    }
};

const formatData = (iso) => {
    if (!iso) return '-';
    return new Date(iso).toLocaleDateString();
};
</script>

<template>
    <Head title="Gerenciar Usuários" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center flex items-center justify-center gap-3">
                    <svg class="w-8 h-8 text-accent-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        Gerenciar Usuários
                    </h1>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Navigation Tabs Administration -->
                <NavegacaoGestao />

                <!-- Tabela de Usuários -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                        <div class="relative w-full max-w-sm">
                            <input v-model="search" type="text" placeholder="Buscar por nome ou e-mail..." class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-sm focus:ring-primary-500 focus:border-primary-500 text-gray-900 dark:text-white">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full whitespace-nowrap text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-100 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th @click="sortBy('name')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                                        <div class="flex items-center gap-1">
                                            Nome completo
                                            <svg v-if="sort.column === 'name'" :class="sort.order === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4">Plano</th>
                                    <th @click="sortBy('role')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            Função
                                            <svg v-if="sort.column === 'role'" :class="sort.order === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4">Assinatura Stripe</th>
                                    <th @click="sortBy('created_at')" class="px-6 py-4 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                                        <div class="flex items-center gap-1">
                                            Cadastro
                                            <svg v-if="sort.column === 'created_at'" :class="sort.order === 'asc' ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ user.name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span v-if="user.plano" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300">
                                            {{ user.plano.name }}
                                        </span>
                                        <span v-else class="text-gray-400 text-xs">Sem Plano</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold',
                                            user.role === 'admin' ? 'bg-danger-100 text-danger-800 border border-danger-200' : 'bg-gray-100 text-gray-800 border border-gray-200'
                                        ]">
                                            {{ user.role === 'admin' ? 'Admin' : 'Membro' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="[
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                            user.stripe_status === 'active' ? 'bg-green-100 text-green-800' : 
                                            (user.stripe_status === 'nenhuma' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800')
                                        ]">
                                            {{ user.stripe_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs">{{ formatData(user.created_at) }}</td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <Link :href="route('admin.users.edit', user.id)" class="inline-flex items-center text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 text-sm font-medium">
                                            Editar
                                        </Link>
                                        <button v-if="user.id !== $page.props.auth.user.id" @click="impersonate(user.id)" class="inline-flex items-center text-accent-600 hover:text-accent-900 dark:text-accent-400 dark:hover:text-accent-300 text-sm font-medium ml-3">
                                            Login Como
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        Nenhum usuário encontrado na pesquisa.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação Simples -->
                    <div v-if="users.links && users.data.length > 0" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-400">
                                    Mostrando de <span class="font-medium">{{ users.from }}</span> a <span class="font-medium">{{ users.to }}</span> de <span class="font-medium">{{ users.total }}</span> resultados
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <template v-for="(link, k) in users.links" :key="k">
                                        <component 
                                            :is="link.url ? Link : 'span'"
                                            :href="link.url"
                                            v-html="link.label"
                                            class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                                            :class="[
                                                link.active ? 'z-10 bg-primary-50 border-primary-500 text-primary-600 dark:bg-primary-900/20' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700',
                                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                            ]"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </AppLayout>
</template>
