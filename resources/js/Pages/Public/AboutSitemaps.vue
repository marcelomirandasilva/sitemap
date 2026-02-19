<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import { onMounted } from 'vue';

const appName = import.meta.env.VITE_APP_NAME;
const anoAtual = new Date().getFullYear();

const mudarIdioma = (lang) => {
    localStorage.setItem('user_locale', lang);
    loadLanguageAsync(lang);
};

onMounted(() => {
    const savedLocale = localStorage.getItem('user_locale');
    if (savedLocale) {
        loadLanguageAsync(savedLocale);
    }
});
</script>

<template>
    <Head :title="$t('articles.about_sitemaps.page_title')" />

    <div class="min-h-screen bg-gradient-to-b from-[#e8f4fc] to-[#f5f5f5] font-sans text-gray-700 flex flex-col">

        <!-- Header Sticky -->
        <div class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center gap-2">
                        <Link href="/" class="text-[#a4332b] hover:opacity-90 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 16h4v4H4v-4zm0-6h4v4H4v-4zm0-6h4v4H4v-4zm6 12h4v4h-4v-4zm0-6h4v4h-4v-4zm0-6h4v4h-4v-4zm6 12h4v4h-4v-4zm0-6h4v4h-4v-4zm0-6h4v4h-4v-4z" />
                                <path d="M2 2h20v20H2V2zm2 2v16h16V4H4z" fill="none" stroke="currentColor" stroke-width="2" />
                            </svg>
                        </Link>
                        <div class="flex flex-col">
                            <Link href="/" class="text-2xl font-bold leading-none tracking-tight text-gray-800 hover:text-[#a4332b] transition">
                                {{ appName }}
                            </Link>
                            <span class="text-xs text-gray-500 tracking-wider">{{ $t('hero.subtitle_tag') }}</span>
                        </div>
                    </div>

                    <nav class="hidden md:flex items-center gap-3 text-sm font-bold text-[#a4332b] uppercase tracking-wide">
                        <Link href="/" class="hover:opacity-80 transition">
                            ← {{ $t('articles.about_sitemaps.back_home') }}
                        </Link>
                        <div class="flex items-center gap-2 border-l border-gray-300 pl-4 ml-2">
                            <button @click="mudarIdioma('pt')" class="hover:scale-110 transition-transform cursor-pointer opacity-80 hover:opacity-100" title="Português">
                                <img src="/flags/br.svg" alt="Português" class="w-5 h-auto shadow-sm rounded-sm" />
                            </button>
                            <button @click="mudarIdioma('en')" class="hover:scale-110 transition-transform cursor-pointer opacity-80 hover:opacity-100" title="English">
                                <img src="/flags/us.svg" alt="English" class="w-5 h-auto shadow-sm rounded-sm" />
                            </button>
                        </div>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-grow py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 md:p-12">
                    
                    <h1 class="text-3xl font-light text-gray-700 mb-8 border-b border-gray-200 pb-4">
                        {{ $t('articles.about_sitemaps.title') }}
                    </h1>

                    <h2 class="text-xl font-semibold text-gray-700 mb-4">
                        {{ $t('articles.about_sitemaps.section1_title') }}
                    </h2>

                    <div class="space-y-4 text-sm text-gray-600 leading-relaxed">
                        <p>{{ $t('articles.about_sitemaps.p1') }}</p>
                        <p>{{ $t('articles.about_sitemaps.p2') }}</p>
                        <p>{{ $t('articles.about_sitemaps.p3') }}</p>
                        <p>{{ $t('articles.about_sitemaps.p4') }}</p>
                        <p>{{ $t('articles.about_sitemaps.p5') }}</p>
                        <p>{{ $t('articles.about_sitemaps.p6') }}</p>
                        <p>{{ $t('articles.about_sitemaps.p7') }}</p>
                    </div>

                    <!-- Related Articles -->
                    <div class="mt-10 pt-6 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">{{ $t('articles.about_sitemaps.related_title') }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <a href="#" class="text-sm text-[#a4332b] hover:underline font-medium">{{ $t('articles.about_sitemaps.related_broken_links') }}</a>
                            <a href="#" class="text-sm text-[#a4332b] hover:underline font-medium">{{ $t('articles.about_sitemaps.related_image') }}</a>
                            <a href="#" class="text-sm text-[#a4332b] hover:underline font-medium">{{ $t('articles.about_sitemaps.related_video') }}</a>
                            <a href="#" class="text-sm text-[#a4332b] hover:underline font-medium">{{ $t('articles.about_sitemaps.related_news') }}</a>
                            <a href="#" class="text-sm text-[#a4332b] hover:underline font-medium">{{ $t('articles.about_sitemaps.related_html') }}</a>
                            <a href="#" class="text-sm text-[#a4332b] hover:underline font-medium">{{ $t('articles.about_sitemaps.related_rss') }}</a>
                            <a href="#" class="text-sm text-[#a4332b] hover:underline font-medium">{{ $t('articles.about_sitemaps.related_text') }}</a>
                            <a href="#" class="text-sm text-[#a4332b] hover:underline font-medium">{{ $t('articles.about_sitemaps.related_mobile') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white pt-10 pb-6">
            <div class="max-w-6xl mx-auto px-4 text-center">
                <nav class="flex flex-wrap justify-center gap-6 mb-6 text-sm text-[#a4332b] font-medium">
                    <Link :href="route('info.article', 'privacy-policy')" class="hover:underline">{{ $t('footer.privacy') }}</Link>
                    <Link :href="route('info.article', 'terms-of-use')" class="hover:underline">{{ $t('footer.terms') }}</Link>
                    <a href="#" class="hover:underline">{{ $t('footer.api') }}</a>
                    <a href="#" class="hover:underline">{{ $t('footer.contact') }}</a>
                    <a href="#" class="hover:underline">{{ $t('footer.help') }}</a>
                </nav>
                <p class="text-xs text-gray-400">
                    &copy; 2005-{{ anoAtual }} {{ appName }}. Todos os direitos reservados.
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    SyNesis Tecnologia.
                </p>
            </div>
        </footer>
    </div>
</template>
