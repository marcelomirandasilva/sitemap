<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME || 'Sitemap';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-50 pt-10 sm:justify-center sm:pt-0">
        <div class="flex flex-col items-center mb-6">
            <Link href="/" class="flex flex-col items-center gap-2 hover:opacity-90 transition-opacity">
                <ApplicationLogo class="h-16 w-16 fill-current text-accent-800" />
                <span class="text-3xl font-bold tracking-tight text-gray-800">{{ appName }}</span>
            </Link>
        </div>

        <div class="mt-4 w-full overflow-hidden bg-white px-8 py-8 shadow-sm sm:max-w-md sm:rounded-2xl border border-gray-100">
            <slot />
        </div>
    </div>
</template>
