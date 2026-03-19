<script setup>
import { Link } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { onMounted } from 'vue';

const mudarIdioma = (lang) => {
    localStorage.setItem('user_locale', lang);
    loadLanguageAsync(lang);
};

const formatTimeAgo = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    // Fallback: Default to PT-BR as requested, but uses the user's selected language seamlessly if possible
    const lang = localStorage.getItem('user_locale') || 'pt';
    
    try {
        const rtf = new Intl.RelativeTimeFormat(lang, { numeric: 'auto', style: 'long' });
        
        if (diffInSeconds < 60) return rtf.format(-Math.max(1, diffInSeconds), 'second');
        const diffInMinutes = Math.floor(diffInSeconds / 60);
        if (diffInMinutes < 60) return rtf.format(-diffInMinutes, 'minute');
        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return rtf.format(-diffInHours, 'hour');
        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 30) return rtf.format(-diffInDays, 'day');
        const diffInMonths = Math.floor(diffInDays / 30);
        if (diffInMonths < 12) return rtf.format(-diffInMonths, 'month');
        const diffInYears = Math.floor(diffInDays / 365);
        return rtf.format(-diffInYears, 'year');
    } catch (e) {
        return date.toLocaleDateString(lang);
    }
};

onMounted(() => {
    const savedLocale = localStorage.getItem('user_locale');
    if (savedLocale) {
        loadLanguageAsync(savedLocale);
    }
});
</script>

<template>
    <nav class="flex items-center justify-end gap-3 text-sm font-bold text-accent-800 uppercase tracking-wide">
        
        <div class="group relative">
            <button class="p-1 text-gray-500 hover:text-accent-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
            <div class="absolute right-0 mt-2 w-64 bg-gray-50 border border-gray-200 shadow-lg rounded-sm p-3 hidden group-hover:block z-50 text-xs text-gray-500 font-normal normal-case text-center">
                <div class="font-bold text-gray-700 border-b pb-1 mb-1 mb-1 border-gray-200">↻ {{ $t('nav.in_progress') }}</div>
                {{ $t('nav.no_crawls') }}
            </div>
        </div>

        <div class="mr-4">
             <Dropdown align="right" width="64">
                <template #trigger>
                    <button class="p-1 text-gray-500 hover:text-accent-800 transition flex items-center">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    </button>
                </template>
                <template #content>
                    <div class="px-4 py-2 text-xs text-gray-500 font-normal normal-case border-b border-gray-100">
                        {{ $t('nav.notifications') }}
                    </div>
                    <div class="px-4 py-4 text-center text-xs text-gray-400 font-normal normal-case">
                        {{ $t('nav.no_notifications') }}
                    </div>
                </template>
             </Dropdown>
        </div>

        <Dropdown align="right" width="64">
            <template #trigger>
                <button class="flex items-center gap-1 hover:opacity-80 transition cursor-pointer">
                    <span class="border border-accent-800 rounded px-1.5 py-0.5 text-xs">{{ $page.props.userProjectsCount }}</span>
                    <span>{{ $t('nav.my_sites') }}</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </template>

            <template #content>
                <DropdownLink :href="route('dashboard')" class="flex items-center gap-2 border-b border-gray-100 mb-1">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    {{ $t('dashboard.control_panel') }}
                </DropdownLink>
                
                <div class="max-h-60 overflow-y-auto">
                    <Link
                        v-for="project in $page.props.userProjects"
                        :key="project.id"
                        :href="route('projects.show', project.id)"
                        class="block px-4 py-2 hover:bg-gray-50 transition border-b border-gray-50 last:border-b-0"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2 w-full">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" 
                                      :class="project.status === 'active' ? 'bg-green-500' : 'bg-red-500'"></span>
                                <span class="text-sm text-gray-700 w-full font-medium truncate leading-tight">{{ project.url }}</span>
                            </div>
                            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded border border-gray-200 text-gray-500 flex-shrink-0 ml-2">
                                {{ project.plan_name }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-400 pl-4 whitespace-nowrap">
                            {{ formatTimeAgo(project.last_crawled_at) || $t('freq.never') }}, {{ project.pages_count }} {{ project.pages_count === 1 ? 'página' : $t('project.pages_count').toLowerCase() }}
                        </div>
                    </Link>
                </div>

                <div v-if="$page.props.userProjects.length === 0" class="px-4 py-3 text-xs text-gray-500 text-center">
                    {{ $t('dashboard.no_websites') }}
                </div>

                <DropdownLink :href="route('dashboard')" class="flex items-center gap-2 border-t border-gray-100 mt-1 pb-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    {{ $t('nav.add_website') }}
                </DropdownLink>
            </template>
        </Dropdown>

        <Dropdown align="right" width="64">
            <template #trigger>
                <button class="flex items-center gap-1 hover:opacity-80 transition cursor-pointer ml-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>{{ $t('nav.account') }}</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </template>

            <template #content>
                <div class="block px-4 py-2 text-xs text-gray-400 border-b border-gray-100 truncate uppercase">
                    {{ $t('nav.your_account') }} - {{ $page.props.auth.user.email }}
                </div>

                <DropdownLink :href="route('preferences.edit')" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    {{ $t('nav.preferences') }}
                </DropdownLink>
                
                <DropdownLink :href="route('billing.index')" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    {{ $t('nav.subscriptions') }}
                </DropdownLink>

                 <DropdownLink href="#" class="flex items-center gap-2 border-b border-gray-100 mb-1">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    {{ $t('nav.api') }}
                </DropdownLink>

                <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    {{ $t('nav.logout') }}
                </DropdownLink>
            </template>
        </Dropdown>

        <Link :href="route('support.index')" class="flex items-center gap-1 hover:opacity-80 transition ml-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            {{ $t('nav.support') }}
        </Link>
        <a href="#" class="flex items-center gap-1 hover:opacity-80 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            {{ $t('nav.help') }}
        </a>

        <div class="flex items-center gap-2 border-l border-gray-300 pl-4 ml-2">
            <button @click="mudarIdioma('pt')" class="hover:scale-110 transition-transform cursor-pointer opacity-80 hover:opacity-100" title="Português">
                <img src="/flags/br.svg" alt="Português" class="w-5 h-auto shadow-sm rounded-sm" />
            </button>
            <button @click="mudarIdioma('en')" class="hover:scale-110 transition-transform cursor-pointer opacity-80 hover:opacity-100" title="English">
                <img src="/flags/us.svg" alt="English" class="w-5 h-auto shadow-sm rounded-sm" />
            </button>
        </div>
    </nav>
</template>