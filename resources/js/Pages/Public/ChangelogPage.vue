<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { loadLanguageAsync, trans as t } from 'laravel-vue-i18n';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import MetaSeoPublico from '@/Components/Public/MetaSeoPublico.vue';

const props = defineProps({
    itens: {
        type: Array,
        default: () => [],
    },
    locale: {
        type: String,
        default: 'pt',
    },
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

const nomeAplicacao = import.meta.env.VITE_APP_NAME;
const anoAtual = new Date().getFullYear();

const localeAtual = computed(() => String(props.locale || 'pt').toLowerCase().startsWith('en') ? 'en' : 'pt');

const mudarIdioma = (idioma) => {
    const destino = props.seo?.alternativas?.[idioma] ?? route('public.changelog', { locale: idioma });
    window.location.href = destino;
};

const formatarData = (dataIso) => {
    if (!dataIso) return '';

    return new Intl.DateTimeFormat(localeAtual.value === 'pt' ? 'pt-BR' : 'en-US', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${dataIso}T00:00:00`));
};

const dataUltimaAtualizacao = computed(() => props.itens?.[0]?.data ?? null);

onMounted(() => {
    loadLanguageAsync(localeAtual.value);
});
</script>

<template>
    <MetaSeoPublico :seo="seo" />

    <div class="min-h-screen bg-bg-page text-text-primary">
        <header class="sticky top-0 z-40 border-b border-border-soft bg-white/90 backdrop-blur-md">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <ApplicationLogo class="h-11 w-auto" :alt="nomeAplicacao" />
                    <div>
                        <div class="text-2xl font-bold tracking-tight">{{ nomeAplicacao }}</div>
                        <div class="text-xs uppercase tracking-[0.22em] text-text-secondary">{{ $t('hero.subtitle_tag', { app_name: nomeAplicacao }) }}</div>
                    </div>
                </div>

                <nav class="hidden items-center gap-5 text-sm font-semibold uppercase tracking-wide text-brand-primary md:flex">
                    <Link :href="route('public.landing', { locale: localeAtual })" class="transition hover:text-brand-secondary">
                        {{ $t('public_changelog.back_home', { app_name: nomeAplicacao }) }}
                    </Link>
                    <div class="ml-2 flex items-center gap-2 border-l border-border-soft pl-4">
                        <button @click="mudarIdioma('pt')" title="Portugues">
                            <img src="/flags/br.svg" alt="Portugues" class="w-5 rounded-sm shadow-sm" />
                        </button>
                        <button @click="mudarIdioma('en')" title="English">
                            <img src="/flags/us.svg" alt="English" class="w-5 rounded-sm shadow-sm" />
                        </button>
                    </div>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 pb-16 pt-10 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-3xl border border-border-soft bg-white shadow-brand-soft">
                <div class="bg-brand-gradient px-6 py-7 text-white sm:px-8">
                    <div class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em]">
                        {{ $t('public_changelog.badge', { app_name: nomeAplicacao }) }}
                    </div>
                    <h1 class="mt-5 text-3xl font-bold sm:text-4xl">{{ $t('public_changelog.title', { app_name: nomeAplicacao }) }}</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-white/85 sm:text-base">
                        {{ $t('public_changelog.description', { app_name: nomeAplicacao }) }}
                    </p>
                    <p v-if="dataUltimaAtualizacao" class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-white/80">
                        {{ $t('public_changelog.last_update', { app_name: nomeAplicacao }) }}: {{ formatarData(dataUltimaAtualizacao) }}
                    </p>
                </div>

                <div class="px-6 py-8 sm:px-8">
                    <div v-if="!itens.length" class="rounded-2xl border border-dashed border-border-soft bg-bg-subtle px-6 py-12 text-center text-sm text-text-secondary">
                        {{ $t('public_changelog.empty', { app_name: nomeAplicacao }) }}
                    </div>

                    <div v-else class="space-y-5">
                        <article
                            v-for="item in itens"
                            :key="`${item.versao}-${item.data}`"
                            class="rounded-2xl border border-border-soft bg-bg-subtle/70 p-6"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="space-y-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-brand-primary px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-white">
                                            v{{ item.versao }}
                                        </span>
                                        <span class="rounded-full border border-border-soft bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-text-secondary">
                                            {{ item.categoria }}
                                        </span>
                                    </div>

                                    <div>
                                        <h2 class="text-xl font-semibold text-text-primary">{{ item.titulo }}</h2>
                                        <p class="mt-2 max-w-3xl text-sm leading-7 text-text-secondary">{{ item.resumo }}</p>
                                    </div>
                                </div>

                                <div class="shrink-0 text-xs font-semibold uppercase tracking-[0.18em] text-text-secondary">
                                    {{ formatarData(item.data) }}
                                </div>
                            </div>

                            <ul v-if="item.itens?.length" class="mt-5 space-y-2 text-sm leading-7 text-text-secondary">
                                <li v-for="linha in item.itens" :key="linha" class="flex items-start gap-3">
                                    <span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-secondary"></span>
                                    <span>{{ linha }}</span>
                                </li>
                            </ul>
                        </article>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-border-soft bg-white pt-10 pb-6">
            <div class="mx-auto max-w-6xl px-4 text-center">
                <nav class="mb-6 flex flex-wrap justify-center gap-6 text-sm font-medium text-brand-secondary">
                    <Link :href="route('public.landing', { locale: localeAtual })" class="hover:text-brand-primary">
                        {{ $t('public_changelog.back_home', { app_name: nomeAplicacao }) }}
                    </Link>
                    <Link :href="route('info.article', { locale: localeAtual, slug: 'privacy-policy' })" class="hover:text-brand-primary">
                        {{ $t('footer.privacy', { app_name: nomeAplicacao }) }}
                    </Link>
                    <Link :href="route('info.article', { locale: localeAtual, slug: 'terms-of-use' })" class="hover:text-brand-primary">
                        {{ $t('footer.terms', { app_name: nomeAplicacao }) }}
                    </Link>
                </nav>
                <p class="text-xs text-text-secondary">&copy; 2005-{{ anoAtual }} {{ nomeAplicacao }}. {{ t('public_changelog.rights', { app_name: nomeAplicacao }) }}</p>
                <p class="mt-2 text-xs text-text-secondary">SyNesis Tecnologia.</p>
            </div>
        </footer>
    </div>
</template>
