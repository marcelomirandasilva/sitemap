<script setup>
import { Head } from '@inertiajs/vue3';
import AppHeader from '@/Components/App/Header.vue';
import AppTopbar from '@/Components/App/Topbar.vue';
import AppFooter from '@/Components/App/Footer.vue';
</script>

<template>
    <div class="min-h-screen bg-[#f5f5f5] font-sans text-gray-700 flex flex-col">
        <!-- Gradient Top Strip -->
        <div class="bg-gradient-to-b from-[#e8f4fc] to-[#f5f5f5] border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Header (Logo) -->
                    <AppHeader />

                    <!-- Topbar (Nav + User) -->
                    <AppTopbar />
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="flex-grow">
            <!-- Breadcrumbs / Page Title Slot -->
            <header v-if="$slots.header" class="bg-white shadow-sm border-b border-gray-100">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <AppFooter />
    </div>
</template>
