<script setup>
import { Link } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const mudarIdioma = (lang) => {
    localStorage.setItem('user_locale', lang);
    loadLanguageAsync(lang);
};
</script>

<template>
    <nav class="flex items-center space-x-6 text-sm font-medium text-gray-600">
        <!-- Main Nav Links -->
        <Link :href="route('dashboard')" :class="{'text-[#a4332b] font-bold': route().current('dashboard')}" class="hover:text-[#a4332b] transition">
            Dashboard
        </Link>
        <a href="#" class="hover:text-[#a4332b] transition">{{ $t('nav.support') }}</a>
        <a href="#" class="hover:text-[#a4332b] transition">{{ $t('nav.help') }}</a>

        <!-- User Dropdown -->
        <div class="relative ml-3">
            <Dropdown align="right" width="48">
                <template #trigger>
                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-[#a4332b] focus:outline-none transition ease-in-out duration-150">
                        {{ $page.props.auth.user.name }}
                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </template>

                <template #content>
                    <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button"> Log Out </DropdownLink>
                </template>
            </Dropdown>
        </div>

        <!-- Language Switcher -->
        <div class="flex items-center gap-2 border-l border-gray-300 pl-4">
            <button @click="mudarIdioma('pt')" class="hover:scale-110 transition-transform cursor-pointer opacity-80 hover:opacity-100" title="Português">
                <img src="/flags/br.svg" alt="Português" class="w-5 h-auto shadow-sm rounded-sm" />
            </button>
            <button @click="mudarIdioma('en')" class="hover:scale-110 transition-transform cursor-pointer opacity-80 hover:opacity-100" title="English">
                <img src="/flags/us.svg" alt="English" class="w-5 h-auto shadow-sm rounded-sm" />
            </button>
        </div>
    </nav>
</template>
