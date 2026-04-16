<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import NavegacaoGestao from '@/Components/Admin/NavegacaoGestao.vue';

const props = defineProps({
    registro: {
        type: Object,
        required: true,
    },
});

const editando = !!props.registro.id;

const form = useForm({
    versao: props.registro.versao || '',
    data_lancamento: props.registro.data_lancamento || '',
    ordem_exibicao: props.registro.ordem_exibicao ?? 0,
    publicado: !!props.registro.publicado,
    categoria_pt: props.registro.categoria_pt || '',
    categoria_en: props.registro.categoria_en || '',
    titulo_pt: props.registro.titulo_pt || '',
    titulo_en: props.registro.titulo_en || '',
    resumo_pt: props.registro.resumo_pt || '',
    resumo_en: props.registro.resumo_en || '',
    itens_pt_texto: props.registro.itens_pt_texto || '',
    itens_en_texto: props.registro.itens_en_texto || '',
});

const salvar = () => {
    if (editando) {
        form.put(route('admin.changelog.update', props.registro.id));
        return;
    }

    form.post(route('admin.changelog.store'));
};
</script>

<template>
    <Head :title="editando ? 'Editar Changelog' : 'Novo Changelog'" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-3">
                    <Link :href="route('admin.changelog.index')" class="text-gray-400 hover:text-gray-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </Link>
                    <h1 class="text-3xl font-light text-gray-700 dark:text-gray-200">
                        {{ editando ? 'Editar Registro do Changelog' : 'Novo Registro do Changelog' }}
                    </h1>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <NavegacaoGestao />

                <form @submit.prevent="salvar" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2 mb-4">Publicacao</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Versao</label>
                                <input v-model="form.versao" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" placeholder="Ex: 1.4.0" required>
                                <InputError :message="form.errors.versao" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de lancamento</label>
                                <input v-model="form.data_lancamento" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.data_lancamento" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ordem de exibicao</label>
                                <input v-model="form.ordem_exibicao" type="number" min="0" max="9999" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.ordem_exibicao" class="mt-2" />
                            </div>
                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-4 dark:border-gray-700 mt-6 md:mt-0">
                                <input v-model="form.publicado" type="checkbox" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div>
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Publicado</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Quando desligado, o item fica fora da pagina publica.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 space-y-5">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2">Conteudo em Portugues</h2>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</label>
                                <input v-model="form.categoria_pt" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.categoria_pt" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Titulo</label>
                                <input v-model="form.titulo_pt" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.titulo_pt" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resumo</label>
                                <textarea v-model="form.resumo_pt" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required></textarea>
                                <InputError :message="form.errors.resumo_pt" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Itens do changelog</label>
                                <textarea v-model="form.itens_pt_texto" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" placeholder="Um item por linha."></textarea>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Cada linha sera exibida como um bullet na pagina publica.</p>
                                <InputError :message="form.errors.itens_pt_texto" class="mt-2" />
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6 space-y-5">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2">Conteudo em Ingles</h2>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                                <input v-model="form.categoria_en" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.categoria_en" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <input v-model="form.titulo_en" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.titulo_en" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Summary</label>
                                <textarea v-model="form.resumo_en" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required></textarea>
                                <InputError :message="form.errors.resumo_en" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Changelog items</label>
                                <textarea v-model="form.itens_en_texto" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" placeholder="One item per line."></textarea>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Each line becomes a bullet in the English public page.</p>
                                <InputError :message="form.errors.itens_en_texto" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-row-reverse items-center gap-4 mb-20 px-2 justify-start">
                        <PrimaryButton :processing="form.processing" type="submit">
                            {{ editando ? 'Salvar alteracoes' : 'Criar registro' }}
                        </PrimaryButton>
                        <Link :href="route('admin.changelog.index')" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition">Cancelar operacao</Link>
                    </div>
                </form>
            </div>
        </template>
    </AppLayout>
</template>
