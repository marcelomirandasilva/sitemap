<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('admin.login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Acesso Restrito - Gestão" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#f8fafc] dark:bg-gray-900 font-sans">
        <div class="w-full sm:max-w-md mt-6 px-10 py-12 bg-white dark:bg-gray-800 shadow-2xl overflow-hidden sm:rounded-xl border border-gray-100 dark:border-gray-700 relative">
            
            <!-- Estilo Decorativo Superior -->
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-primary-500 to-accent-500"></div>

            <div class="flex flex-col items-center mb-10">
                <div class="p-4 bg-primary-50 dark:bg-primary-900 rounded-full mb-4">
                    <svg class="w-10 h-10 text-primary-600 dark:text-primary-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-light text-gray-800 dark:text-white mt-2">Sitemap <span class="font-bold text-primary-600">Gestão</span></h2>
                <p class="text-gray-400 text-sm mt-1 uppercase tracking-widest font-medium">Acesso Restrito ao Staff</p>
            </div>

            <div v-if="status" class="mb-6 font-medium text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 p-3 rounded border border-green-100 dark:border-green-800">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div class="mb-5">
                    <label class="block font-bold text-xs text-gray-400 uppercase tracking-wider mb-2" for="email">E-mail Corporativo</label>
                    <input
                        id="email"
                        type="email"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition outline-none text-gray-700 dark:text-gray-200"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="seu@email.com"
                    />
                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1 font-medium italic italic">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block font-bold text-xs text-gray-400 uppercase tracking-wider mb-2" for="password">Senha de Segurança</label>
                    <input
                        id="password"
                        type="password"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition outline-none text-gray-700 dark:text-gray-200"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <div v-if="form.errors.password" class="text-red-500 text-xs mt-1 font-medium italic">
                        {{ form.errors.password }}
                    </div>
                </div>

                <div class="block mb-8">
                    <label class="flex items-center cursor-pointer group">
                        <div class="relative">
                            <input type="checkbox" name="remember" v-model="form.remember" class="sr-only" />
                            <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full shadow-inner transition group-hover:bg-gray-300 dark:group-hover:bg-gray-600"></div>
                            <div class="absolute left-0 w-5 h-5 bg-white rounded-full shadow transition transform" :class="{'translate-x-5 bg-primary-500': form.remember}"></div>
                        </div>
                        <span class="ml-3 text-sm text-gray-500 dark:text-gray-400 font-medium">Manter conectado neste terminal</span>
                    </label>
                </div>

                <div class="flex items-center justify-between mt-8">
                    <Link
                        v-if="true"
                        :href="route('password.request')"
                        class="text-sm text-gray-400 hover:text-primary-600 transition font-medium"
                    >
                        Esqueceu a senha?
                    </Link>

                    <button
                        class="px-8 py-3 bg-gray-900 dark:bg-primary-600 text-white rounded-lg font-bold text-sm uppercase tracking-widest hover:bg-primary-600 dark:hover:bg-primary-500 transition-all shadow-lg active:scale-95 disabled:opacity-50 flex items-center gap-2"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing">Autenticando...</span>
                        <span v-else>Entrar no Sistema</span>
                        <svg v-if="!form.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
        
        <p class="mt-8 text-gray-400 text-xs font-medium">
            &copy; {{ new Date().getFullYear() }} Sitemap. Todos os direitos reservados.
        </p>
    </div>
</template>
