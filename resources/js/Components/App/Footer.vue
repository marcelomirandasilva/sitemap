<script setup>
import { usePage, Link } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME;
const anoAtual = new Date().getFullYear();
const page = usePage();
</script>

<template>
    <footer class="border-t border-border-soft bg-white pt-10 pb-6">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <nav class="mb-6 flex flex-wrap justify-center gap-6 text-sm font-medium text-brand-secondary">
                <Link :href="route('info.article', { locale: page.props.locale ?? 'pt', slug: 'privacy-policy' })" class="hover:underline">{{ $t('footer.privacy') }}</Link>
                <Link :href="route('info.article', { locale: page.props.locale ?? 'pt', slug: 'terms-of-use' })" class="hover:underline">{{ $t('footer.terms') }}</Link>
                <a href="#" class="hover:underline">{{ $t('footer.api') }}</a>
                <a href="#" class="hover:underline">{{ $t('footer.contact') }}</a>
                <a href="#" class="hover:underline">{{ $t('footer.help') }}</a>
            </nav>
            <p class="text-xs text-text-secondary">
                &copy; 2005-{{ anoAtual }} {{ appName }}. Todos os direitos reservados.
            </p>
            <p class="mt-2 text-xs text-text-secondary">
                SyNesis Tecnologia.
            </p>
        </div>
    </footer>
</template>
