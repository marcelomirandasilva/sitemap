<script setup>
import { usePage, Link } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME;
const anoAtual = new Date().getFullYear();
</script>

<template>
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
</template>
