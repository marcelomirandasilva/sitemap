<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    seo: {
        type: Object,
        default: () => ({
            title: '',
            description: '',
            canonical: '',
            robots: '',
            alternativas: {},
        }),
    },
});

const seoNormalizado = computed(() => ({
    title: '',
    description: '',
    canonical: '',
    robots: '',
    alternativas: {},
    ...(props.seo ?? {}),
}));

const alternativas = computed(() => seoNormalizado.value.alternativas ?? {});
</script>

<template>
    <Head :title="seoNormalizado.title || undefined">
        <meta v-if="seoNormalizado.description" head-key="description" name="description" :content="seoNormalizado.description">
        <meta v-if="seoNormalizado.robots" head-key="robots" name="robots" :content="seoNormalizado.robots">
        <link v-if="seoNormalizado.canonical" head-key="canonical" rel="canonical" :href="seoNormalizado.canonical">

        <template v-for="(urlAlternativa, idioma) in alternativas" :key="`hreflang-${idioma}`">
            <link rel="alternate" :hreflang="idioma" :href="urlAlternativa">
        </template>

        <meta v-if="seoNormalizado.title" head-key="og:title" property="og:title" :content="seoNormalizado.title">
        <meta v-if="seoNormalizado.description" head-key="og:description" property="og:description" :content="seoNormalizado.description">
        <meta v-if="seoNormalizado.canonical" head-key="og:url" property="og:url" :content="seoNormalizado.canonical">
        <meta head-key="og:type" property="og:type" content="website">

        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image">
        <meta v-if="seoNormalizado.title" head-key="twitter:title" name="twitter:title" :content="seoNormalizado.title">
        <meta v-if="seoNormalizado.description" head-key="twitter:description" name="twitter:description" :content="seoNormalizado.description">
    </Head>
</template>
