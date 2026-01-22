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

onMounted(() => {
    const savedLocale = localStorage.getItem('user_locale');
    if (savedLocale) {
        loadLanguageAsync(savedLocale);
    }
});
</script>

<template>
    <nav class="flex items-center justify-end gap-3 text-sm font-bold text-[#a4332b] uppercase tracking-wide">
        
        <div class="group relative">
            <button class="p-1 text-gray-500 hover:text-[#a4332b] transition">
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
                    <button class="p-1 text-gray-500 hover:text-[#a4332b] transition flex items-center">
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

        <Dropdown align="right" width="48">
            <template #trigger>
                <button class="flex items-center gap-1 hover:opacity-80 transition cursor-pointer">
                    <span class="border border-[#a4332b] rounded px-1.5 py-0.5 text-xs">0</span>
                    <span>{{ $t('nav.my_sites') }}</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </template>

            <template #content>
                <DropdownLink :href="route('dashboard')" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    {{ $t('dashboard.title') }}
                </DropdownLink>
                <DropdownLink :href="route('dashboard')" class="flex items-center gap-2">
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

                <DropdownLink :href="route('profile.edit')" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    {{ $t('nav.preferences') }}
                </DropdownLink>
                
                <DropdownLink href="#" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    {{ $t('nav.subscriptions') }}
                </DropdownLink>

                <DropdownLink href="#" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    {{ $t('nav.invoices') }}
                </DropdownLink>

                <DropdownLink href="#" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    {{ $t('nav.payment_details') }}
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

        <a href="#" class="flex items-center gap-1 hover:opacity-80 transition ml-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            {{ $t('nav.support') }}
        </a>
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