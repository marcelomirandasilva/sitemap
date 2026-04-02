<script setup>
import { computed } from "vue";
import { trans as t } from "laravel-vue-i18n";

const props = defineProps({
    relatorioSeo: {
        type: Object,
        default: () => ({
            disponivel: false,
            total_paginas: 0,
            total_links: 0,
            total_links_internos: 0,
            total_links_externos: 0,
            total_links_quebrados: 0,
            paginas_com_links_quebrados: 0,
            paginas_sem_links_entrada: 0,
            paginas_sem_links_saida: 0,
            profundidade_maxima: 0,
            estrutura: {
                diretorios_principais: [],
                distribuicao_profundidade: [],
                paginas_mais_referenciadas: [],
                paginas_sem_links_entrada: [],
                paginas_sem_links_saida: [],
            },
            amostras: {
                links_quebrados: [],
                links_externos: [],
            },
        }),
    },
});

const resumoCards = computed(() => [
    { chave: "paginas", titulo: t("project.seo_total_pages"), valor: props.relatorioSeo?.total_paginas ?? 0 },
    { chave: "links", titulo: t("project.seo_total_links"), valor: props.relatorioSeo?.total_links ?? 0 },
    { chave: "internos", titulo: t("project.seo_internal_links"), valor: props.relatorioSeo?.total_links_internos ?? 0 },
    { chave: "externos", titulo: t("project.seo_external_links"), valor: props.relatorioSeo?.total_links_externos ?? 0 },
    { chave: "quebrados", titulo: t("project.seo_broken_links"), valor: props.relatorioSeo?.total_links_quebrados ?? 0 },
    { chave: "profundidade", titulo: t("project.seo_max_depth"), valor: props.relatorioSeo?.profundidade_maxima ?? 0 },
]);
</script>

<template>
    <div class="space-y-8">
        <div class="bg-primary-50 border border-primary-100 rounded-lg p-5">
            <h3 class="text-lg font-bold text-primary-900 mb-2">{{ $t("project.seo_title") }}</h3>
            <p class="text-sm text-primary-800">{{ $t("project.seo_intro") }}</p>
        </div>

        <div v-if="!relatorioSeo?.disponivel" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center text-sm text-gray-500">
            {{ $t("project.seo_empty") }}
        </div>

        <template v-else>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="card in resumoCards" :key="card.chave" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ card.titulo }}</div>
                    <div class="mt-2 text-3xl font-bold text-gray-800">{{ card.valor }}</div>
                </div>
            </div>

            <div v-if="(relatorioSeo?.total_links ?? 0) === 0" class="rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                {{ $t("project.seo_links_notice") }}
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h4 class="text-base font-bold text-gray-800">{{ $t("project.seo_broken_links_section") }}</h4>
                        <p class="text-sm text-gray-500">{{ $t("project.seo_broken_links_help") }}</p>
                    </div>

                    <div v-if="!relatorioSeo?.amostras?.links_quebrados?.length" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        {{ $t("project.seo_broken_links_empty") }}
                    </div>

                    <div v-else class="space-y-3">
                        <article v-for="(link, index) in relatorioSeo.amostras.links_quebrados" :key="`${link.source_url}-${link.target_url}-${index}`" class="rounded-lg border border-danger-100 bg-danger-50/40 p-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full border border-danger-200 bg-danger-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-danger-700">
                                    HTTP {{ link.status_code }}
                                </span>
                                <span v-if="link.anchor_text" class="text-xs text-gray-500">{{ link.anchor_text }}</span>
                            </div>
                            <div class="mt-3 space-y-1 text-sm">
                                <div class="text-gray-700">
                                    <span class="font-semibold">{{ $t("project.seo_source_page") }}:</span>
                                    <span class="break-all">{{ link.source_url }}</span>
                                </div>
                                <div class="text-danger-700">
                                    <span class="font-semibold">{{ $t("project.seo_target_page") }}:</span>
                                    <span class="break-all">{{ link.target_url }}</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h4 class="text-base font-bold text-gray-800">{{ $t("project.seo_external_links_section") }}</h4>
                        <p class="text-sm text-gray-500">{{ $t("project.seo_external_links_help") }}</p>
                    </div>

                    <div v-if="!relatorioSeo?.amostras?.links_externos?.length" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        {{ $t("project.seo_external_links_empty") }}
                    </div>

                    <div v-else class="space-y-3">
                        <article v-for="(link, index) in relatorioSeo.amostras.links_externos" :key="`${link.target_url}-${index}`" class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-800 break-all">{{ link.target_url }}</div>
                                    <div class="mt-1 text-xs text-gray-500">{{ link.dominio }}</div>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-gray-600">
                                    {{ link.ocorrencias }}x
                                </span>
                            </div>
                            <div class="mt-3 text-sm text-gray-600 break-all">
                                <span class="font-semibold">{{ $t("project.seo_source_page") }}:</span> {{ link.source_url }}
                            </div>
                        </article>
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <h4 class="text-base font-bold text-gray-800">{{ $t("project.seo_structure_section") }}</h4>
                        <p class="text-sm text-gray-500">{{ $t("project.seo_structure_help") }}</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t("project.seo_orphan_pages") }}</div>
                            <div class="mt-2 text-2xl font-bold text-gray-800">{{ relatorioSeo.paginas_sem_links_entrada }}</div>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <div class="text-[11px] font-bold uppercase tracking-wide text-gray-400">{{ $t("project.seo_dead_end_pages") }}</div>
                            <div class="mt-2 text-2xl font-bold text-gray-800">{{ relatorioSeo.paginas_sem_links_saida }}</div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="text-sm font-bold text-gray-700 mb-3">{{ $t("project.seo_top_directories") }}</div>
                        <div v-if="!relatorioSeo?.estrutura?.diretorios_principais?.length" class="text-sm text-gray-500">
                            {{ $t("project.seo_structure_empty") }}
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="item in relatorioSeo.estrutura.diretorios_principais" :key="item.diretorio" class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3">
                                <span class="font-medium text-gray-700">{{ item.diretorio }}</span>
                                <span class="text-sm font-bold text-gray-900">{{ item.total }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="text-sm font-bold text-gray-700 mb-3">{{ $t("project.seo_depth_distribution") }}</div>
                        <div v-if="!relatorioSeo?.estrutura?.distribuicao_profundidade?.length" class="text-sm text-gray-500">
                            {{ $t("project.seo_structure_empty") }}
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="item in relatorioSeo.estrutura.distribuicao_profundidade" :key="item.profundidade" class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3">
                                <span class="font-medium text-gray-700">{{ $t("project.seo_depth_level") }} {{ item.profundidade }}</span>
                                <span class="text-sm font-bold text-gray-900">{{ item.total }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-6">
                    <div>
                        <h4 class="text-base font-bold text-gray-800">{{ $t("project.seo_internal_linking_section") }}</h4>
                        <p class="text-sm text-gray-500">{{ $t("project.seo_internal_linking_help") }}</p>
                    </div>

                    <div>
                        <div class="text-sm font-bold text-gray-700 mb-3">{{ $t("project.seo_most_linked_pages") }}</div>
                        <div v-if="!relatorioSeo?.estrutura?.paginas_mais_referenciadas?.length" class="text-sm text-gray-500">
                            {{ $t("project.seo_structure_empty") }}
                        </div>
                        <div v-else class="space-y-3">
                            <article v-for="(pagina, index) in relatorioSeo.estrutura.paginas_mais_referenciadas" :key="`${pagina.url}-${index}`" class="rounded-lg border border-gray-100 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-800 break-all">{{ pagina.url }}</div>
                                        <div v-if="pagina.title" class="mt-1 text-xs text-gray-500">{{ pagina.title }}</div>
                                    </div>
                                    <span class="inline-flex items-center rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-primary-700">
                                        {{ pagina.total }}x
                                    </span>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-bold text-gray-700 mb-3">{{ $t("project.seo_pages_without_incoming") }}</div>
                        <div v-if="!relatorioSeo?.estrutura?.paginas_sem_links_entrada?.length" class="text-sm text-gray-500">
                            {{ $t("project.seo_structure_empty") }}
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="(pagina, index) in relatorioSeo.estrutura.paginas_sem_links_entrada" :key="`${pagina.url}-${index}`" class="rounded-lg border border-gray-100 px-4 py-3">
                                <div class="text-sm font-medium text-gray-700 break-all">{{ pagina.url }}</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-sm font-bold text-gray-700 mb-3">{{ $t("project.seo_pages_without_outgoing") }}</div>
                        <div v-if="!relatorioSeo?.estrutura?.paginas_sem_links_saida?.length" class="text-sm text-gray-500">
                            {{ $t("project.seo_structure_empty") }}
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="(pagina, index) in relatorioSeo.estrutura.paginas_sem_links_saida" :key="`${pagina.url}-${index}`" class="rounded-lg border border-gray-100 px-4 py-3">
                                <div class="text-sm font-medium text-gray-700 break-all">{{ pagina.url }}</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </template>
    </div>
</template>
