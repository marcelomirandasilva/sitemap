<script setup>
import { computed } from 'vue';
import { trans as t } from 'laravel-vue-i18n';

const props = defineProps({
    seoBilingue: {
        type: Object,
        default: () => ({
            disponivel: false,
            site_multilingue: false,
            total_paginas: 0,
            idiomas_detectados: [],
            paginas_com_hreflang: 0,
            paginas_sem_hreflang: 0,
            paginas_sem_canonical: 0,
            paginas_sem_autorreferencia: 0,
            paginas_com_x_default: 0,
            amostras: {
                sem_canonical: [],
                sem_hreflang: [],
                sem_autorreferencia: [],
            },
        }),
    },
});

const idiomasDetectados = computed(() => props.seoBilingue?.idiomas_detectados ?? []);
const amostras = computed(() => props.seoBilingue?.amostras ?? {});

const formatarIdioma = (codigo) => {
    if (!codigo) return '-';
    if (codigo === 'x-default') return 'x-default';
    return codigo.toUpperCase();
};

const formatarTituloItem = (item) => {
    return item?.title || item?.url || '-';
};

const formatarResumoAlternativas = (item) => {
    const links = item?.hreflang_links ?? [];
    if (!links.length) return '-';
    return links.map((link) => `${formatarIdioma(link.lang)} -> ${link.url}`).join(' | ');
};
</script>

<template>
    <div class="space-y-8">
        <div class="bg-primary-50 border border-primary-100 rounded-lg p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-primary-900 mb-2">{{ $t('project.seo_bilingual_title') }}</h3>
                    <p class="text-sm text-primary-800">{{ $t('project.seo_bilingual_intro') }}</p>
                </div>

                <span
                    :class="[
                        'inline-flex items-center rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wide',
                        seoBilingue.site_multilingue
                            ? 'border-green-200 bg-green-50 text-green-700'
                            : 'border-gray-200 bg-gray-50 text-gray-500'
                    ]"
                >
                    {{ seoBilingue.site_multilingue
                        ? $t('project.seo_bilingual_site_detected')
                        : $t('project.seo_bilingual_site_not_detected') }}
                </span>
            </div>
        </div>

        <div
            v-if="!seoBilingue.disponivel"
            class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500"
        >
            {{ $t('project.seo_bilingual_empty') }}
        </div>

        <template v-else>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                        {{ $t('project.seo_bilingual_detected_languages') }}
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="idioma in idiomasDetectados"
                            :key="idioma.codigo"
                            class="inline-flex items-center gap-2 rounded-full border border-primary-100 bg-primary-50 px-3 py-1 text-xs font-bold text-primary-700"
                        >
                            {{ formatarIdioma(idioma.codigo) }}
                            <span class="text-primary-900">{{ idioma.total }}</span>
                        </span>
                        <span v-if="idiomasDetectados.length === 0" class="text-sm text-gray-400">
                            -
                        </span>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                        {{ $t('project.seo_bilingual_pages_with_hreflang') }}
                    </div>
                    <div class="mt-3 text-3xl font-light text-gray-900">{{ seoBilingue.paginas_com_hreflang }}</div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                        {{ $t('project.seo_bilingual_pages_without_hreflang') }}
                    </div>
                    <div class="mt-3 text-3xl font-light text-gray-900">{{ seoBilingue.paginas_sem_hreflang }}</div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                        {{ $t('project.seo_bilingual_pages_without_canonical') }}
                    </div>
                    <div class="mt-3 text-3xl font-light text-gray-900">{{ seoBilingue.paginas_sem_canonical }}</div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-500">
                        {{ $t('project.seo_bilingual_pages_without_self_reference') }}
                    </div>
                    <div class="mt-3 text-3xl font-light text-gray-900">{{ seoBilingue.paginas_sem_autorreferencia }}</div>
                    <div class="mt-2 text-xs text-gray-500">
                        x-default: {{ seoBilingue.paginas_com_x_default }}
                    </div>
                </div>
            </div>

            <div v-if="!seoBilingue.site_multilingue" class="rounded-xl border border-gray-200 bg-white px-6 py-5 text-sm text-gray-600 shadow-sm">
                {{ $t('project.seo_bilingual_not_multilingual') }}
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-bold uppercase tracking-wide text-gray-700">
                        {{ $t('project.seo_bilingual_missing_canonical') }}
                    </h4>
                    <div v-if="(amostras.sem_canonical ?? []).length === 0" class="mt-4 text-sm text-gray-400">
                        {{ $t('project.seo_bilingual_issue_empty') }}
                    </div>
                    <div v-else class="mt-4 space-y-4">
                        <div v-for="item in amostras.sem_canonical" :key="`canonical-${item.url}`" class="border-b border-gray-100 pb-4 last:border-b-0 last:pb-0">
                            <div class="text-sm font-semibold text-gray-800 break-all">{{ formatarTituloItem(item) }}</div>
                            <div class="mt-1 text-xs text-gray-500 break-all">{{ item.url }}</div>
                            <div class="mt-2 text-[11px] uppercase tracking-wide text-gray-500">
                                {{ $t('project.seo_bilingual_issue_language') }}: {{ formatarIdioma(item.language) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-bold uppercase tracking-wide text-gray-700">
                        {{ $t('project.seo_bilingual_missing_hreflang') }}
                    </h4>
                    <div v-if="(amostras.sem_hreflang ?? []).length === 0" class="mt-4 text-sm text-gray-400">
                        {{ $t('project.seo_bilingual_issue_empty') }}
                    </div>
                    <div v-else class="mt-4 space-y-4">
                        <div v-for="item in amostras.sem_hreflang" :key="`hreflang-${item.url}`" class="border-b border-gray-100 pb-4 last:border-b-0 last:pb-0">
                            <div class="text-sm font-semibold text-gray-800 break-all">{{ formatarTituloItem(item) }}</div>
                            <div class="mt-1 text-xs text-gray-500 break-all">{{ item.url }}</div>
                            <div class="mt-2 text-[11px] uppercase tracking-wide text-gray-500">
                                {{ $t('project.seo_bilingual_issue_language') }}: {{ formatarIdioma(item.language) }}
                            </div>
                            <div class="mt-1 text-[11px] uppercase tracking-wide text-gray-500">
                                {{ $t('project.seo_bilingual_issue_canonical') }}: {{ item.canonical_url || '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h4 class="text-sm font-bold uppercase tracking-wide text-gray-700">
                        {{ $t('project.seo_bilingual_missing_self_reference') }}
                    </h4>
                    <div v-if="(amostras.sem_autorreferencia ?? []).length === 0" class="mt-4 text-sm text-gray-400">
                        {{ $t('project.seo_bilingual_issue_empty') }}
                    </div>
                    <div v-else class="mt-4 space-y-4">
                        <div v-for="item in amostras.sem_autorreferencia" :key="`self-${item.url}`" class="border-b border-gray-100 pb-4 last:border-b-0 last:pb-0">
                            <div class="text-sm font-semibold text-gray-800 break-all">{{ formatarTituloItem(item) }}</div>
                            <div class="mt-1 text-xs text-gray-500 break-all">{{ item.url }}</div>
                            <div class="mt-2 text-[11px] uppercase tracking-wide text-gray-500">
                                {{ $t('project.seo_bilingual_issue_hreflang_count') }}: {{ item.total_hreflang }}
                            </div>
                            <div class="mt-1 text-xs text-gray-500 break-all">
                                {{ formatarResumoAlternativas(item) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
