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
    profundidade_maxima_padrao: props.plano.profundidade_maxima_padrao ?? 3,
    profundidade_maxima_limite: props.plano.profundidade_maxima_limite ?? 3,
    concorrencia_padrao: props.plano.concorrencia_padrao ?? 2,
    concorrencia_limite: props.plano.concorrencia_limite ?? 2,
    atraso_padrao_segundos: props.plano.atraso_padrao_segundos ?? 1,
    atraso_minimo_segundos: props.plano.atraso_minimo_segundos ?? 1,
    atraso_maximo_segundos: props.plano.atraso_maximo_segundos ?? 1,
    intervalo_personalizado_padrao_horas: props.plano.intervalo_personalizado_padrao_horas ?? 24,
    has_advanced_features: !!props.plano.has_advanced_features,
    permite_imagens: !!props.plano.permite_imagens,
    permite_videos: !!props.plano.permite_videos,
    permite_noticias: !!props.plano.permite_noticias,
    permite_mobile: !!props.plano.permite_mobile,
    permite_compactacao: !!props.plano.permite_compactacao,
    permite_cache_crawler: !!props.plano.permite_cache_crawler,
    permite_padroes_exclusao: !!props.plano.permite_padroes_exclusao,
    permite_politicas_crawl: !!props.plano.permite_politicas_crawl,
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
        return;
    }

    form.post(route('admin.plans.store'));
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ideal para</label>
                                <input v-model="form.ideal_for" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" placeholder="Ex: agências e operações com vários sites">
                                <InputError :message="form.errors.ideal_for" class="mt-2" />
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frequência de Atualização Automática</label>
                                <select v-model="form.update_frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <option value="" disabled>Selecione uma frequência</option>
                                    <option value="diario">Diária</option>
                                    <option value="semanal">Semanal</option>
                                    <option value="quinzenal">Quinzenal</option>
                                    <option value="mensal">Mensal</option>
                                    <option value="anual">Anual</option>
                                    <option value="customizado">Customizada por projeto</option>
                                    <option value="manual">Sob Demanda</option>
                                </select>
                                <InputError :message="form.errors.update_frequency" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2 mb-4">Limites Operacionais</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Máximo de Projetos <span class="text-xs text-gray-400 font-normal">(-1 para ilimitado)</span></label>
                                <input v-model="form.max_projects" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.max_projects" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Máximo de Páginas <span class="text-xs text-gray-400 font-normal">(-1 para ilimitado)</span></label>
                                <input v-model="form.max_pages" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                <InputError :message="form.errors.max_pages" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300 mb-4">Padrões Técnicos do Projeto</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profundidade padrão</label>
                                    <input v-model="form.profundidade_maxima_padrao" type="number" min="1" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <InputError :message="form.errors.profundidade_maxima_padrao" class="mt-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profundidade máxima</label>
                                    <input v-model="form.profundidade_maxima_limite" type="number" min="1" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <InputError :message="form.errors.profundidade_maxima_limite" class="mt-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Concorrência padrão</label>
                                    <input v-model="form.concorrencia_padrao" type="number" min="1" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <InputError :message="form.errors.concorrencia_padrao" class="mt-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Concorrência máxima</label>
                                    <input v-model="form.concorrencia_limite" type="number" min="1" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <InputError :message="form.errors.concorrencia_limite" class="mt-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Atraso padrão (s)</label>
                                    <input v-model="form.atraso_padrao_segundos" type="number" min="0.1" max="10" step="0.1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <InputError :message="form.errors.atraso_padrao_segundos" class="mt-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Atraso mínimo (s)</label>
                                    <input v-model="form.atraso_minimo_segundos" type="number" min="0.1" max="10" step="0.1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <InputError :message="form.errors.atraso_minimo_segundos" class="mt-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Atraso máximo (s)</label>
                                    <input v-model="form.atraso_maximo_segundos" type="number" min="0.1" max="10" step="0.1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <InputError :message="form.errors.atraso_maximo_segundos" class="mt-2" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Intervalo customizado padrão (h)</label>
                                    <input v-model="form.intervalo_personalizado_padrao_horas" type="number" min="1" max="720" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" required>
                                    <InputError :message="form.errors.intervalo_personalizado_padrao_horas" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2 mb-4">Recursos Vendáveis do Plano</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                            Esses itens aparecem nas telas de plano e controlam o que o cliente consegue configurar no projeto.
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.has_advanced_features" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Configurações Avançadas</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Profundidade, concorrência, atraso, user-agent e acesso à API.</span>
                                </div>
                            </label>

                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.permite_imagens" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sitemap de Imagens</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Permite gerar e rastrear imagens no sitemap.</span>
                                </div>
                            </label>

                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.permite_videos" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sitemap de Vídeos</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Permite gerar e rastrear vídeos no sitemap.</span>
                                </div>
                            </label>

                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.permite_noticias" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sitemap de Notícias</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Libera o rastreamento de conteúdo voltado a Google News.</span>
                                </div>
                            </label>

                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.permite_mobile" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sitemap Mobile</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Permite sitemap dedicado para páginas mobile.</span>
                                </div>
                            </label>

                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.permite_padroes_exclusao" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Padrões de Exclusão</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Regex e padrões para ignorar áreas do site no crawl.</span>
                                </div>
                            </label>

                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.permite_politicas_crawl" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Políticas de Crawl</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Presets reutilizáveis de escopo, filtros e limites do crawler.</span>
                                </div>
                            </label>

                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.permite_cache_crawler" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Controle de Cache</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Deixa o cliente ativar ou desativar o cache do crawler.</span>
                                </div>
                            </label>

                            <label class="flex items-start rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <input v-model="form.permite_compactacao" type="checkbox" class="mt-1 rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600">
                                <div class="ml-3">
                                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Saída Compactada</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">Permite ao cliente solicitar artefatos compactados.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b dark:border-gray-700 pb-2 mb-4">Integração Stripe e Preços</h2>
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Mensal (BRL em centavos)</label>
                                <input v-model="form.price_monthly_brl" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.price_monthly_brl" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Anual (BRL em centavos)</label>
                                <input v-model="form.price_yearly_brl" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.price_yearly_brl" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Mensal (USD em cents)</label>
                                <input v-model="form.price_monthly_usd" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.price_monthly_usd" class="mt-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preço Anual (USD em cents)</label>
                                <input v-model="form.price_yearly_usd" type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                                <InputError :message="form.errors.price_yearly_usd" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-row-reverse items-center gap-4 mb-20 px-2 justify-start">
                        <PrimaryButton :processing="form.processing" type="submit">
                            {{ isEditing ? 'Gravar Alterações' : 'Criar Plano' }}
                        </PrimaryButton>
                        <Link :href="route('admin.plans.index')" class="text-sm font-medium text-gray-500 hover:text-gray-900 transition">Cancelar Operação</Link>
                    </div>
                </form>
            </div>
        </template>
    </AppLayout>
</template>
