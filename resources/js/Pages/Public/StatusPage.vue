<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { loadLanguageAsync, trans as t } from 'laravel-vue-i18n';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import MetaSeoPublico from '@/Components/Public/MetaSeoPublico.vue';

const props = defineProps({
    status: {
        type: Object,
        default: () => ({
            status_geral: 'degraded',
            atualizado_em: null,
            componentes: [],
        }),
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
    const destino = props.seo?.alternativas?.[idioma] ?? route('public.status', { locale: idioma });
    window.location.href = destino;
};

const formatarDataHora = (dataIso) => {
    if (!dataIso) return t('public_status.not_available', { app_name: nomeAplicacao });

    return new Intl.DateTimeFormat(localeAtual.value === 'pt' ? 'pt-BR' : 'en-US', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dataIso));
};

const classesStatus = {
    operational: 'bg-success-50 text-success-600 border-success-500/20',
    degraded: 'bg-warning-50 text-warning-700 border-warning-500/20',
    outage: 'bg-danger-50 text-danger-600 border-danger-500/20',
};

const rotulosStatus = computed(() => ({
    operational: t('public_status.operational', { app_name: nomeAplicacao }),
    degraded: t('public_status.degraded', { app_name: nomeAplicacao }),
    outage: t('public_status.outage', { app_name: nomeAplicacao }),
}));

const classeStatusGeral = computed(() => classesStatus[props.status?.status_geral] ?? classesStatus.degraded);
const rotuloStatusGeral = computed(() => rotulosStatus.value[props.status?.status_geral] ?? rotulosStatus.value.degraded);

const valorDetalhe = (valor) => {
    if (valor === null || valor === undefined || valor === '') {
        return t('public_status.not_available', { app_name: nomeAplicacao });
    }

    if (typeof valor === 'boolean') {
        return valor ? 'true' : 'false';
    }

    return String(valor);
};

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
                        {{ $t('public_status.back_home', { app_name: nomeAplicacao }) }}
                    </Link>
                    <Link :href="route('public.changelog', { locale: localeAtual })" class="transition hover:text-brand-secondary">
                        {{ $t('nav.changelog', { app_name: nomeAplicacao }) }}
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
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <div class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em]">
                                {{ $t('public_status.badge', { app_name: nomeAplicacao }) }}
                            </div>
                            <h1 class="mt-5 text-3xl font-bold sm:text-4xl">{{ $t('public_status.title', { app_name: nomeAplicacao }) }}</h1>
                            <p class="mt-3 max-w-3xl text-sm leading-7 text-white/85 sm:text-base">
                                {{ $t('public_status.description', { app_name: nomeAplicacao }) }}
                            </p>
                            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-white/80">
                                {{ $t('public_status.updated_at', { app_name: nomeAplicacao }) }}: {{ formatarDataHora(status.atualizado_em) }}
                            </p>
                        </div>

                        <div :class="['inline-flex items-center rounded-full border px-4 py-2 text-xs font-bold uppercase tracking-[0.18em]', classeStatusGeral]">
                            {{ rotuloStatusGeral }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 px-6 py-8 sm:px-8 lg:grid-cols-3">
                    <article
                        v-for="componente in status.componentes"
                        :key="componente.slug"
                        class="rounded-2xl border border-border-soft bg-bg-subtle/70 p-5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-text-primary">{{ componente.nome }}</h2>
                                <p class="mt-2 text-sm leading-7 text-text-secondary">{{ componente.mensagem }}</p>
                            </div>
                            <span :class="['shrink-0 rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em]', classesStatus[componente.status] ?? classesStatus.degraded]">
                                {{ rotulosStatus[componente.status] ?? rotulosStatus.degraded }}
                            </span>
                        </div>

                        <dl v-if="componente.detalhes" class="mt-5 space-y-3 border-t border-border-soft pt-4 text-sm">
                            <div
                                v-for="(valor, chave) in componente.detalhes"
                                :key="`${componente.slug}-${chave}`"
                                class="flex items-start justify-between gap-4"
                            >
                                <dt class="font-medium text-text-secondary">{{ chave }}</dt>
                                <dd class="text-right text-text-primary">{{ valorDetalhe(valor) }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </section>
        </main>

        <footer class="border-t border-border-soft bg-white pt-10 pb-6">
            <div class="mx-auto max-w-6xl px-4 text-center">
                <nav class="mb-6 flex flex-wrap justify-center gap-6 text-sm font-medium text-brand-secondary">
                    <Link :href="route('public.landing', { locale: localeAtual })" class="hover:text-brand-primary">
                        {{ $t('public_status.back_home', { app_name: nomeAplicacao }) }}
                    </Link>
                    <Link :href="route('public.changelog', { locale: localeAtual })" class="hover:text-brand-primary">
                        {{ $t('footer.changelog', { app_name: nomeAplicacao }) }}
                    </Link>
                    <Link :href="route('info.article', { locale: localeAtual, slug: 'privacy-policy' })" class="hover:text-brand-primary">
                        {{ $t('footer.privacy', { app_name: nomeAplicacao }) }}
                    </Link>
                    <Link :href="route('info.article', { locale: localeAtual, slug: 'terms-of-use' })" class="hover:text-brand-primary">
                        {{ $t('footer.terms', { app_name: nomeAplicacao }) }}
                    </Link>
                </nav>
                <p class="text-xs text-text-secondary">&copy; 2005-{{ anoAtual }} {{ nomeAplicacao }}. {{ t('public_status.rights', { app_name: nomeAplicacao }) }}</p>
                <p class="mt-2 text-xs text-text-secondary">SyNesis Tecnologia.</p>
            </div>
        </footer>
    </div>
</template>
