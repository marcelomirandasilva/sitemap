<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { loadLanguageAsync, trans as t } from 'laravel-vue-i18n';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import MetaSeoPublico from '@/Components/Public/MetaSeoPublico.vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    defaultTab: { type: String, default: 'signup' },
    plans: { type: Array, default: () => [] },
    locale: { type: String, default: 'pt' },
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

const normalizarLocale = (locale) => String(locale || 'pt').toLowerCase().startsWith('en') ? 'en' : 'pt';
const nomeAplicacao = import.meta.env.VITE_APP_NAME;
const anoAtual = new Date().getFullYear();
const abaAtiva = ref(props.defaultTab || 'signup');
const localeAtual = computed(() => normalizarLocale(props.locale));
const moedaAtual = ref(localeAtual.value === 'pt' ? 'BRL' : 'USD');

onMounted(() => {
    loadLanguageAsync(localeAtual.value);
});

const mudarIdioma = (idioma) => {
    const destino = props.seo?.alternativas?.[idioma] ?? route('public.landing', { locale: idioma });
    window.location.href = destino;
};

const precoMensal = (plano) => moedaAtual.value === 'BRL' ? plano.price_monthly_brl : plano.price_monthly_usd;
const precoAnual = (plano) => moedaAtual.value === 'BRL' ? plano.price_yearly_brl : plano.price_yearly_usd;

const formatarPreco = (plano, tipo) => {
    const valor = tipo === 'monthly' ? precoMensal(plano) : (precoAnual(plano) / 12);

    if (valor <= 0) {
        return moedaAtual.value === 'BRL' ? 'R$ 0' : '$0';
    }

    return new Intl.NumberFormat(moedaAtual.value === 'BRL' ? 'pt-BR' : 'en-US', {
        style: 'currency',
        currency: moedaAtual.value,
    }).format(valor / 100);
};

const formularioCadastro = useForm({
    name: '',
    url: '',
    email: '',
    terms: false,
});

const formularioLogin = useForm({
    email: '',
    password: '',
    remember: false,
});

const formatarUrl = () => {
    const url = formularioCadastro.url.trim();

    if (url && !/^https?:\/\//i.test(url)) {
        formularioCadastro.url = `https://${url}`;
    }
};

const enviarCadastro = () => {
    formularioCadastro.post(route('register'), {
        onFinish: () => formularioCadastro.reset(),
    });
};

const enviarLogin = () => {
    formularioLogin.post(route('login'), {
        onFinish: () => formularioLogin.reset('password'),
    });
};

const recursosPlano = (plano) => {
    const recursos = [];

    if (plano.permite_imagens) recursos.push(t('subscription.features.images'));
    if (plano.permite_videos) recursos.push(t('subscription.features.videos'));
    if (plano.permite_noticias) recursos.push(t('subscription.features.news'));
    if (plano.permite_mobile) recursos.push(t('subscription.features.mobile'));
    if (plano.permite_padroes_exclusao) recursos.push(t('subscription.features.exclude_patterns'));
    if (plano.permite_politicas_crawl) recursos.push(t('subscription.features.crawl_policies'));
    if (plano.permite_cache_crawler) recursos.push(t('subscription.features.cache'));
    if (plano.permite_compactacao) recursos.push(t('subscription.features.compression'));
    if (plano.has_advanced_features) recursos.push(t('subscription.features.api'));

    return recursos.length > 0 ? recursos : [t('subscription.features.basic')];
};
</script>

<template>
    <MetaSeoPublico :seo="seo" />

    <div class="min-h-screen bg-bg-page text-text-primary">
        <div v-if="$page.props.flash.success" class="fixed right-4 top-4 z-50 rounded-xl border border-success-500/20 bg-success-50 px-4 py-3 text-sm text-success-600 shadow-brand-soft">
            <p class="font-semibold">Sucesso</p>
            <p>{{ $page.props.flash.success }}</p>
        </div>
        <div v-if="$page.props.flash.error" class="fixed right-4 top-4 z-50 rounded-xl border border-danger-500/20 bg-danger-50 px-4 py-3 text-sm text-danger-600 shadow-brand-soft">
            <p class="font-semibold">Erro</p>
            <p>{{ $page.props.flash.error }}</p>
        </div>

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
                    <Link :href="route('public.status', { locale: localeAtual })" class="transition hover:text-brand-secondary">
                        {{ $t('nav.status', { app_name: nomeAplicacao }) }}
                    </Link>
                    <Link :href="route('public.changelog', { locale: localeAtual })" class="transition hover:text-brand-secondary">
                        {{ $t('nav.changelog', { app_name: nomeAplicacao }) }}
                    </Link>
                    <Link :href="route('info.article', { locale: localeAtual, slug: 'terms-of-use' })" class="transition hover:text-brand-secondary">
                        {{ $t('nav.help', { app_name: nomeAplicacao }) }}
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

        <main class="mx-auto max-w-7xl px-4 pb-16 pt-10 sm:px-6 lg:px-8">
            <section class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="space-y-6">
                    <div class="inline-flex rounded-full border border-brand-accent/20 bg-accent-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-brand-secondary">
                        Sitemap Gen
                    </div>
                    <div>
                        <h1 class="max-w-3xl text-4xl font-bold leading-tight sm:text-5xl">
                            {{ $t('hero.main_title', { app_name: nomeAplicacao }) }}
                        </h1>
                        <p class="mt-4 max-w-2xl text-lg leading-8 text-text-secondary">
                            {{ $t('hero.subtitle', { app_name: nomeAplicacao }) }}
                        </p>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="superficie-marca p-5">
                            <div class="text-2xl font-bold text-brand-primary">23M+</div>
                            <div class="mt-2 text-sm text-text-secondary">{{ $t('info.provided_sitemaps', { app_name: nomeAplicacao }) }}</div>
                        </div>
                        <div class="superficie-marca p-5">
                            <div class="text-2xl font-bold text-brand-primary">380k+</div>
                            <div class="mt-2 text-sm text-text-secondary">{{ $t('info.website_owners', { app_name: nomeAplicacao }) }}</div>
                        </div>
                        <div class="superficie-marca p-5">
                            <div class="text-2xl font-bold text-brand-primary">99.9%</div>
                            <div class="mt-2 text-sm text-text-secondary">{{ $t('info.uptime', { app_name: nomeAplicacao }) }}</div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl border border-border-soft bg-white shadow-brand-soft">
                    <div class="bg-brand-gradient px-6 py-4 text-white">
                        <div class="text-sm font-semibold uppercase tracking-[0.2em]">
                            {{ abaAtiva === 'signup' ? $t('auth.signup_tab', { app_name: nomeAplicacao }) : $t('auth.login_tab', { app_name: nomeAplicacao }) }}
                        </div>
                    </div>

                    <div class="p-6 sm:p-8">
                        <div class="mb-6 flex justify-center">
                            <div class="inline-flex rounded-full border border-border-soft bg-bg-subtle p-1">
                                <button @click="abaAtiva = 'signup'" :class="abaAtiva === 'signup' ? 'bg-white text-brand-primary shadow-sm' : 'text-text-secondary'" class="rounded-full px-6 py-2 text-sm font-semibold transition">
                                    {{ $t('auth.signup_tab', { app_name: nomeAplicacao }) }}
                                </button>
                                <button @click="abaAtiva = 'login'" :class="abaAtiva === 'login' ? 'bg-white text-brand-primary shadow-sm' : 'text-text-secondary'" class="rounded-full px-6 py-2 text-sm font-semibold transition">
                                    {{ $t('auth.login_tab', { app_name: nomeAplicacao }) }}
                                </button>
                            </div>
                        </div>

                        <form v-if="abaAtiva === 'signup'" @submit.prevent="enviarCadastro" class="space-y-4">
                            <input v-model="formularioCadastro.url" @blur="formatarUrl" type="url" required :placeholder="'* ' + $t('form.url_placeholder', { app_name: nomeAplicacao })" class="w-full rounded-xl border border-border-soft bg-bg-subtle px-4 py-3 text-sm placeholder:text-text-secondary focus:border-brand-secondary focus:ring-brand-secondary">
                            <input v-model="formularioCadastro.email" type="email" required :placeholder="$t('form.email_create_account', { app_name: nomeAplicacao })" class="w-full rounded-xl border border-border-soft bg-bg-subtle px-4 py-3 text-sm placeholder:text-text-secondary focus:border-brand-secondary focus:ring-brand-secondary">
                            <input v-model="formularioCadastro.name" type="text" required :placeholder="$t('form.name_placeholder', { app_name: nomeAplicacao })" class="w-full rounded-xl border border-border-soft bg-bg-subtle px-4 py-3 text-sm placeholder:text-text-secondary focus:border-brand-secondary focus:ring-brand-secondary">
                            <label class="flex items-start gap-3 rounded-xl border border-border-soft bg-bg-subtle px-4 py-3 text-xs leading-6 text-text-secondary">
                                <input v-model="formularioCadastro.terms" type="checkbox" required class="mt-1 h-4 w-4 rounded border-border-strong text-brand-primary focus:ring-brand-secondary">
                                <span>
                                    * {{ $t('auth.agree_prefix', { app_name: nomeAplicacao }) }}
                                    <Link :href="route('info.article', { locale: localeAtual, slug: 'privacy-policy' })" class="font-semibold text-brand-secondary">{{ $t('auth.privacy_policy', { app_name: nomeAplicacao }) }}</Link>
                                    {{ $t('auth.and', { app_name: nomeAplicacao }) }}
                                    <Link :href="route('info.article', { locale: localeAtual, slug: 'terms-of-use' })" class="font-semibold text-brand-secondary">{{ $t('auth.terms_of_use', { app_name: nomeAplicacao }) }}</Link>
                                    {{ $t('auth.service_suffix', { app_name: nomeAplicacao }) }}
                                </span>
                            </label>
                            <button :disabled="formularioCadastro.processing" class="w-full rounded-xl bg-brand-primary px-6 py-3 text-sm font-bold uppercase tracking-[0.2em] text-white transition hover:bg-primary-700 disabled:opacity-50">
                                {{ formularioCadastro.processing ? 'PROCESSANDO...' : $t('hero.cta', { app_name: nomeAplicacao }) }}
                            </button>
                        </form>

                        <form v-else @submit.prevent="enviarLogin" class="space-y-4">
                            <input v-model="formularioLogin.email" type="email" :placeholder="'* ' + $t('auth.email_placeholder', { app_name: nomeAplicacao })" class="w-full rounded-xl border border-border-soft bg-bg-subtle px-4 py-3 text-sm placeholder:text-text-secondary focus:border-brand-secondary focus:ring-brand-secondary">
                            <input v-model="formularioLogin.password" type="password" :placeholder="'* ' + $t('auth.password_placeholder', { app_name: nomeAplicacao })" class="w-full rounded-xl border border-border-soft bg-bg-subtle px-4 py-3 text-sm placeholder:text-text-secondary focus:border-brand-secondary focus:ring-brand-secondary">
                            <button :disabled="formularioLogin.processing" class="w-full rounded-xl bg-brand-primary px-6 py-3 text-sm font-bold uppercase tracking-[0.2em] text-white transition hover:bg-primary-700 disabled:opacity-50">
                                {{ formularioLogin.processing ? '...' : $t('auth.login_tab', { app_name: nomeAplicacao }).toUpperCase() }}
                            </button>
                            <div class="text-center">
                                <a href="#" class="text-sm font-semibold text-brand-secondary">{{ $t('auth.forgot_password', { app_name: nomeAplicacao }) }}</a>
                            </div>
                        </form>

                        <div class="my-5 flex items-center gap-3">
                            <div class="h-px flex-1 bg-border-soft"></div>
                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-text-secondary">{{ $t('auth.or', { app_name: nomeAplicacao }) }}</span>
                            <div class="h-px flex-1 bg-border-soft"></div>
                        </div>

                        <a :href="route('auth.google')" class="flex items-center justify-center gap-3 rounded-xl border border-border-soft bg-white px-6 py-3 text-sm font-semibold text-text-primary shadow-sm transition hover:border-brand-secondary/30 hover:bg-bg-subtle">
                            <svg class="h-5 w-5" viewBox="0 0 24 24">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.26.81-.58z" fill="#FBBC05" />
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                            </svg>
                            {{ $t('auth.google', { app_name: nomeAplicacao }) }}
                        </a>
                    </div>
                </div>
            </section>

            <section class="mt-10 grid gap-8 lg:grid-cols-2">
                <div class="superficie-marca p-8">
                    <h2 class="text-2xl font-semibold">{{ $t('info.title', { app_name: nomeAplicacao }) }}</h2>
                    <div class="mt-6 space-y-6 text-sm leading-7 text-text-secondary">
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary">{{ $t('info.what_title', { app_name: nomeAplicacao }) }}</h3>
                            <p class="mt-2">{{ $t('info.what_text', { app_name: nomeAplicacao }) }}</p>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-primary">{{ $t('info.who_title', { app_name: nomeAplicacao }) }}</h3>
                            <p class="mt-2">{{ $t('info.who_text', { app_name: nomeAplicacao }) }}</p>
                        </div>
                        <Link :href="route('info.article', { locale: localeAtual, slug: 'about-sitemaps' })" class="inline-flex font-semibold text-brand-secondary">
                            {{ $t('info.more_about', { app_name: nomeAplicacao }) }}
                        </Link>
                    </div>
                </div>

                <div class="superficie-marca p-8">
                    <h2 class="text-2xl font-semibold">{{ $t('info.advantages_title', { app_name: nomeAplicacao }) }}</h2>
                    <ul class="mt-6 space-y-4 text-sm leading-7 text-text-secondary">
                        <li><strong class="text-text-primary">{{ $t('info.adv1_bold', { app_name: nomeAplicacao }) }}</strong> {{ $t('info.adv1_text', { app_name: nomeAplicacao }) }}</li>
                        <li>{{ $t('info.adv2_pre', { app_name: nomeAplicacao }) }} <strong class="text-text-primary">{{ $t('info.adv2_bold', { app_name: nomeAplicacao }) }}</strong> {{ $t('info.adv2_text', { app_name: nomeAplicacao }) }}</li>
                        <li><strong class="text-text-primary">{{ $t('info.adv3_bold', { app_name: nomeAplicacao }) }}</strong> {{ $t('info.adv3_text', { app_name: nomeAplicacao }) }}</li>
                        <li>{{ $t('info.adv4_pre', { app_name: nomeAplicacao }) }} <strong class="text-text-primary">{{ $t('info.adv4_bold', { app_name: nomeAplicacao }) }}</strong> {{ $t('info.adv4_text', { app_name: nomeAplicacao }) }}</li>
                        <li>{{ $t('info.adv5_pre', { app_name: nomeAplicacao }) }} <strong class="text-text-primary">{{ $t('info.adv5_bold', { app_name: nomeAplicacao }) }}</strong> {{ $t('info.adv5_text', { app_name: nomeAplicacao }) }}</li>
                        <li>{{ $t('info.adv6_pre', { app_name: nomeAplicacao }) }} <strong class="text-text-primary">{{ $t('info.adv6_bold', { app_name: nomeAplicacao }) }}</strong> {{ $t('info.adv6_text', { app_name: nomeAplicacao }) }}</li>
                    </ul>
                </div>
            </section>

            <section class="mt-10 overflow-x-auto rounded-3xl border border-border-soft bg-white shadow-brand-soft">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead class="bg-bg-subtle">
                        <tr>
                            <th class="px-6 py-4 font-semibold uppercase tracking-[0.18em]">{{ $t('pricing.table.plan', { app_name: nomeAplicacao }) }}</th>
                            <th class="px-6 py-4 text-center font-semibold uppercase tracking-[0.18em]">{{ $t('pricing.table.monthly', { app_name: nomeAplicacao }) }}</th>
                            <th class="px-6 py-4 text-center font-semibold uppercase tracking-[0.18em]">{{ $t('pricing.table.yearly', { app_name: nomeAplicacao }) }}</th>
                            <th class="px-6 py-4 text-center font-semibold uppercase tracking-[0.18em]">{{ $t('pricing.table.limit', { app_name: nomeAplicacao }) }}</th>
                            <th class="px-6 py-4 font-semibold uppercase tracking-[0.18em]">{{ $t('pricing.table.update', { app_name: nomeAplicacao }) }}</th>
                            <th class="px-6 py-4 font-semibold uppercase tracking-[0.18em]">{{ $t('pricing.table.ideal', { app_name: nomeAplicacao }) }}</th>
                            <th class="px-6 py-4 font-semibold uppercase tracking-[0.18em]">{{ $t('pricing.table.resources', { app_name: nomeAplicacao }) }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-soft">
                        <tr v-for="plano in plans" :key="plano.id" class="hover:bg-bg-subtle/80">
                            <td class="px-6 py-5 font-semibold">{{ plano.name }}</td>
                            <td class="px-6 py-5 text-center font-semibold text-brand-primary">{{ formatarPreco(plano, 'monthly') }}</td>
                            <td class="px-6 py-5 text-center">
                                <span v-if="precoAnual(plano) > 0" class="font-semibold text-brand-secondary">{{ formatarPreco(plano, 'yearly_monthly') }}</span>
                                <span v-else class="text-text-secondary">-</span>
                            </td>
                            <td class="px-6 py-5 text-center font-semibold">{{ new Intl.NumberFormat('en-US').format(plano.max_pages) }}</td>
                            <td class="px-6 py-5 text-text-secondary">{{ $t(`pricing.plans.${plano.slug}.frequency`, { app_name: nomeAplicacao }) }}</td>
                            <td class="px-6 py-5 italic text-text-secondary">{{ $t(`pricing.plans.${plano.slug}.ideal_for`, { app_name: nomeAplicacao }) }}</td>
                            <td class="px-6 py-5">
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="recurso in recursosPlano(plano)" :key="`${plano.id}-${recurso}`" class="rounded-full border border-border-soft bg-bg-subtle px-3 py-1 text-[11px] font-medium text-brand-secondary">
                                        {{ recurso }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </main>

        <footer class="border-t border-border-soft bg-white pt-10 pb-6">
            <div class="mx-auto max-w-6xl px-4 text-center">
                <nav class="mb-6 flex flex-wrap justify-center gap-6 text-sm font-medium text-brand-secondary">
                    <Link :href="route('info.article', { locale: localeAtual, slug: 'privacy-policy' })" class="hover:text-brand-primary">{{ $t('footer.privacy', { app_name: nomeAplicacao }) }}</Link>
                    <Link :href="route('info.article', { locale: localeAtual, slug: 'terms-of-use' })" class="hover:text-brand-primary">{{ $t('footer.terms', { app_name: nomeAplicacao }) }}</Link>
                    <Link :href="route('public.status', { locale: localeAtual })" class="hover:text-brand-primary">{{ $t('footer.status', { app_name: nomeAplicacao }) }}</Link>
                    <Link :href="route('public.changelog', { locale: localeAtual })" class="hover:text-brand-primary">{{ $t('footer.changelog', { app_name: nomeAplicacao }) }}</Link>
                    <a href="#" class="hover:text-brand-primary">{{ $t('footer.api', { app_name: nomeAplicacao }) }}</a>
                    <a href="#" class="hover:text-brand-primary">{{ $t('footer.contact', { app_name: nomeAplicacao }) }}</a>
                    <a href="#" class="hover:text-brand-primary">{{ $t('footer.help', { app_name: nomeAplicacao }) }}</a>
                </nav>
                <p class="text-xs text-text-secondary">&copy; 2005-{{ anoAtual }} {{ nomeAplicacao }}. Todos os direitos reservados.</p>
                <p class="mt-2 text-xs text-text-secondary">SyNesis Tecnologia.</p>
            </div>
        </footer>
    </div>
</template>
