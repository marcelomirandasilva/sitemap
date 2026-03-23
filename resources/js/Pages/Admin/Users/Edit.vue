<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    user: Object,
    planos: Array,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    plan_id: props.user.plan_id || '',
    role: props.user.role,
    password: '',
});

const salvar = () => {
    form.put(route('admin.users.update', props.user.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Editar Usuário" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-3">
                    <Link :href="route('admin.users.index')" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </Link>
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        Editar Usuário <span class="font-semibold">{{ user.name }}</span>
                    </h1>
                </div>
            </div>

            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                    <form @submit.prevent="salvar" class="space-y-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nome -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome Mestre</label>
                                <input v-model="form.name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
                                <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.email" class="mt-2" />
                            </div>

                            <!-- Plano -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plano Vinculado</label>
                                <select v-model="form.plan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                    <option value="">Sem Plano (Free)</option>
                                    <option v-for="plano in planos" :key="plano.id" :value="plano.id">{{ plano.name }}</option>
                                </select>
                                <InputError :message="form.errors.plan_id" class="mt-2" />
                            </div>

                            <!-- Role -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nível de Acesso (Função)</label>
                                <select v-model="form.role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <option value="user">Membro Cliente</option>
                                    <option value="admin">Administrador do Sistema</option>
                                </select>
                                <InputError :message="form.errors.role" class="mt-2" />
                            </div>

                            <!-- Nova Senha -->
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Forçar Nova Senha <span class="font-normal text-xs text-gray-400">(Deixe em branco para ignorar)</span></label>
                                <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" placeholder="********">
                                <InputError :message="form.errors.password" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end border-t border-gray-200 dark:border-gray-700 pt-5 space-x-3">
                            <Link :href="route('admin.users.index')" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">Cancelar</Link>
                            <PrimaryButton :processing="form.processing" type="submit">Atualizar Conta</PrimaryButton>
                        </div>

                    </form>
                </div>

            </div>
        </template>
    </AppLayout>
</template>
