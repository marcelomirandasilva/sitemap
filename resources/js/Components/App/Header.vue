<script setup>
import { Link } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div class="flex items-center gap-2">
        <Link :href="route('dashboard')" class="text-[#a4332b] hover:opacity-90 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4 16h4v4H4v-4zm0-6h4v4H4v-4zm0-6h4v4H4v-4zm6 12h4v4h-4v-4zm0-6h4v4h-4v-4zm0-6h4v4h-4v-4zm6 12h4v4h-4v-4zm0-6h4v4h-4v-4zm0-6h4v4h-4v-4z" />
                <path d="M2 2h20v20H2V2zm2 2v16h16V4H4z" fill="none" stroke="currentColor" stroke-width="2" />
            </svg>
        </Link>
        <div class="flex flex-col">
            <Link :href="route('dashboard')" class="text-2xl font-bold leading-none tracking-tight text-gray-800 hover:text-[#a4332b] transition">
                {{ appName }}
            </Link>
            <span class="text-xs text-gray-500 tracking-wider">{{ $t('hero.subtitle_tag') }}</span>
        </div>
    </div>
</template>
