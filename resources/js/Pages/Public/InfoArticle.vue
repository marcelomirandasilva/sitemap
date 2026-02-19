<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import { onMounted, computed } from 'vue';

const appName = import.meta.env.VITE_APP_NAME;
const anoAtual = new Date().getFullYear();

const props = defineProps({
    article: Object,
    slug: String,
    allSlugs: Array,
});

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

// Mapa de slugs para chaves de tradução dos labels do sidebar
const sidebarLabels = {
    'about-sitemaps': 'articles.about_sitemaps.related_about',
    'broken-links': 'articles.about_sitemaps.related_broken_links',
    'images-sitemap': 'articles.about_sitemaps.related_image',
    'video-sitemap': 'articles.about_sitemaps.related_video',
    'news-sitemap': 'articles.about_sitemaps.related_news',
    'html-sitemap': 'articles.about_sitemaps.related_html',
    'rss-feed': 'articles.about_sitemaps.related_rss',
    'text-sitemap': 'articles.about_sitemaps.related_text',
    'mobile-sitemap': 'articles.about_sitemaps.related_mobile',
    'privacy-policy': 'legal.privacy.sidebar_label',
    'terms-of-use': 'legal.terms.sidebar_label',
};

const otherArticles = computed(() => {
    return props.allSlugs.filter(s => s !== props.slug);
});
</script>

<template>
    <Head :title="$t(article.page_title_key)" />

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
                            ← {{ $t('about_sitemaps.back_home') }}
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
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    
                    <!-- Main Article -->
                    <div class="lg:col-span-3">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 md:p-12">
                            <h1 class="text-3xl font-light text-gray-700 mb-8 border-b border-gray-200 pb-4">
                                {{ $t(article.title_key) }}
                            </h1>

                            <h2 class="text-xl font-semibold text-gray-700 mb-4">
                                {{ $t(article.section_title_key) }}
                            </h2>

                            <div class="space-y-4 text-sm text-gray-600 leading-relaxed">
                                <p v-for="(pKey, idx) in article.paragraphs_keys" :key="idx">
                                    {{ $t(pKey) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sticky top-28">
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">
                                {{ $t('about_sitemaps.related_title') }}
                            </h3>
                            <nav class="space-y-2">
                                <Link
                                    v-for="articleSlug in allSlugs"
                                    :key="articleSlug"
                                    :href="route('info.article', articleSlug)"
                                    class="block text-sm font-medium transition-colors px-3 py-2 rounded-lg"
                                    :class="articleSlug === slug
                                        ? 'bg-[#a4332b] text-white'
                                        : 'text-[#a4332b] hover:bg-gray-50'"
                                >
                                    {{ $t(sidebarLabels[articleSlug] || articleSlug) }}
                                </Link>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white pt-10 pb-6">
            <div class="max-w-6xl mx-auto px-4 text-center">
                <nav class="flex flex-wrap justify-center gap-6 mb-6 text-sm text-[#a4332b] font-medium">
                    <a href="#" class="hover:underline">{{ $t('footer.privacy') }}</a>
                    <a href="#" class="hover:underline">{{ $t('footer.terms') }}</a>
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
