<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

// Formulário para adicionar o site
const form = useForm({
    url: '',
});

const submitProject = () => {
    form.post(route('projects.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head :title="$t('dashboard.title')" />

    <AppLayout>
        <template #hero>
            <div class="max-w-4xl mx-auto mt-8 text-center px-4">
                <h1 class="text-2xl font-light text-gray-700 flex items-center justify-center gap-2">
                    <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                    </svg>
                    {{ $t('dashboard.title') }}
                </h1>
            </div>

            <div class="max-w-2xl mx-auto mt-6 bg-white shadow-xl border border-gray-200 p-12 text-center rounded-lg">
                
                <h2 class="text-xl font-light text-gray-600 mb-6">
                    {{ $t('dashboard.no_sites') }}
                </h2>
                
                <hr class="border-gray-100 mb-8 w-1/2 mx-auto">

                <h3 class="text-lg font-light text-gray-500 mb-4">
                    {{ $t('dashboard.manage_sites') }}
                </h3>

                <form @submit.prevent="submitProject" class="max-w-md mx-auto">
                    <div class="relative">
                        <input 
                            v-model="form.url"
                            type="url" 
                            required
                            :placeholder="$t('dashboard.url_placeholder')"
                            class="w-full border border-gray-300 shadow-inner px-4 py-3 text-gray-600 focus:ring-1 focus:ring-blue-400 focus:border-blue-400 italic bg-gray-50 transition rounded-md"
                        >
                        <div v-if="form.errors.url" class="text-red-500 text-xs mt-1 text-left">
                            {{ form.errors.url }}
                        </div>
                    </div>

                    <div class="mt-6">
                        <button 
                            :disabled="form.processing"
                            type="submit"
                            class="bg-[#007da0] hover:bg-[#006480] text-white font-bold py-3 px-8 rounded text-xs uppercase tracking-wider transition shadow-sm disabled:opacity-50"
                        >
                            {{ form.processing ? $t('dashboard.adding_button') : $t('dashboard.add_button') }}
                        </button>
                    </div>
                </form>
                
                <div class="mt-8 border-t border-gray-100 pt-4"></div>
            </div>
        </template>
    </AppLayout>
</template>