<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div class="flex items-center gap-3">
        <Link :href="route('dashboard')" class="transition hover:opacity-90">
            <ApplicationLogo class="h-11 w-auto" :alt="appName" />
        </Link>

        <div class="flex flex-col">
            <Link
                :href="route('dashboard')"
                class="text-2xl font-bold leading-none tracking-tight text-text-primary transition hover:text-brand-primary"
            >
                {{ appName }}
            </Link>
            <span class="text-xs tracking-[0.24em] text-text-secondary">
                {{ $t('hero.subtitle_tag') }}
            </span>
        </div>
    </div>
</template>
