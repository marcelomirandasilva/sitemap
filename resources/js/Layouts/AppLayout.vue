<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppHeader from '@/Components/App/Header.vue';
import AppTopbar from '@/Components/App/Topbar.vue';
import AppFooter from '@/Components/App/Footer.vue';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const requiresVerification = computed(() => user.value && user.value.email_verified_at === null);
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-[#e8f4fc] to-[#f5f5f5] font-sans text-gray-700 flex flex-col">
        
        <div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <AppHeader />

                    <AppTopbar />
                </div>
            </div>
            <div v-if="$slots.hero" class="pb-12">
                <slot name="hero" />
            </div>
        </div>

        <main class="flex-grow">
            <header v-if="$slots.header" class="bg-white/50 backdrop-blur-sm shadow-sm border-b border-gray-100">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <div class="max-w-7xl mx-auto py-8 sm:py-12 px-4 sm:px-6 lg:px-8">
                <!-- Banner de Ativação de Conta -->
                <div v-if="requiresVerification" class="mb-8">
                    <div class="bg-[#fdf0f0] border border-[#f5c6cb] rounded md:px-6 text-[#721c24] px-4 py-4 text-center text-sm shadow-sm">
                        <div class="flex items-center justify-center gap-2 mb-2 flex-wrap">
                            <svg class="w-4 h-4 text-[#c0392b]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ $t('auth.not_activated') }}</span>
                        </div>
                        <div class="mb-1">{{ $t('auth.check_spam') }}</div>
                        <div class="font-bold">
                            <Link :href="route('verification.notice')" class="text-[#c0392b] hover:underline cursor-pointer bg-transparent border-none p-0 inline">{{ $t('auth.click_resend') }}</Link> 
                            <span class="font-normal"> {{ $t('auth.to_resend_or') }} </span> 
                            <a href="/support" class="text-[#c0392b] hover:underline">{{ $t('auth.open_ticket') }}</a>.
                        </div>
                    </div>
                </div>

                <slot />
            </div>
        </main>

        <AppFooter />
    </div>
</template>