<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const loading = ref(false);
const result = ref(null);
const error = ref(null);

const runTest = async () => {
    loading.value = true;
    result.value = null;
    error.value = null;

    try {
        const response = await axios.post('/dev/api-test/run');
        result.value = response.data;
    } catch (err) {
        error.value = err.response?.data?.message || err.message;
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Test API Connection" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Teste de Conexão API Sitemaps
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="mb-6">
                        <p class="text-gray-600 mb-4">
                            Esta página verifica se o Laravel consegue se autenticar na API Python definida em <code>.env</code>.
                        </p>
                        
                        <button 
                            @click="runTest" 
                            :disabled="loading"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50 transition"
                        >
                            {{ loading ? 'Testando...' : 'Testar Conexão' }}
                        </button>
                    </div>

                    <div v-if="result" :class="['p-4 rounded border', result.success ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200']">
                        <h3 :class="['font-bold text-lg', result.success ? 'text-green-800' : 'text-red-800']">
                            {{ result.success ? 'Sucesso!' : 'Falha' }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-700">
                            <strong>Mensagem:</strong> {{ result.message }}
                        </p>
                        <p class="text-sm text-gray-700">
                            <strong>URL Base:</strong> {{ result.base_url }}
                        </p>
                        <p class="text-sm text-gray-700">
                            <strong>Latência:</strong> {{ result.duration_ms }}ms
                        </p>
                        <p v-if="result.status" class="text-sm text-gray-700">
                            <strong>Status Code:</strong> {{ result.status }}
                        </p>
                        <p v-if="result.response_body" class="text-xs text-red-600 mt-2 font-mono bg-red-50 p-2 rounded whitespace-pre-wrap">
                            Erro: {{ JSON.stringify(result.response_body, null, 2) }}
                        </p>
                        <p v-if="result.debug_creds" class="text-xs text-gray-400 mt-1">
                            Debug: Integrado como {{ result.debug_creds.user }} (Senha: {{ result.debug_creds.pass_len }} chars)
                        </p>
                        <div v-if="result.token_preview" class="text-sm text-gray-500 mt-2 font-mono bg-gray-100 p-2 rounded">
                            Token: {{ result.token_preview }}
                        </div>
                    </div>

                    <div v-if="error" class="p-4 rounded bg-red-100 border border-red-300 text-red-800">
                        <strong>Erro Crítico:</strong> {{ error }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
