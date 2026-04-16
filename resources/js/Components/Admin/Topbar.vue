<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { loadLanguageAsync } from 'laravel-vue-i18n';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { computed, watch } from 'vue';

const page = usePage();
const idiomaAtual = computed(() => {
    const idioma = String(page.props.locale || localStorage.getItem('user_locale') || 'pt').toLowerCase();

    if (idioma.startsWith('en')) {
        return 'en';
    }

    return 'pt';
});

const mudarIdioma = (lang) => {
    if (lang === idiomaAtual.value) {
        return;
    }

    router.put(route('preferences.locale.update'), { locale: lang }, {
        preserveScroll: true,
        preserveState: true,
        only: ['locale', 'auth'],
        onSuccess: () => {
            localStorage.setItem('user_locale', lang);
            loadLanguageAsync(lang);
        },
    });
};

watch(
    () => page.props.locale,
    (novoIdioma) => {
        const idioma = String(novoIdioma || 'pt').toLowerCase().startsWith('en') ? 'en' : 'pt';
        localStorage.setItem('user_locale', idioma);
        loadLanguageAsync(idioma);
    },
    { immediate: true }
);
</script>

<template>
    <nav class="flex items-center justify-end gap-6 text-sm font-bold text-gray-500 uppercase tracking-widest">
        <Link :href="route('dashboard')" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-primary-50 text-gray-600 hover:text-primary-700 transition border border-gray-200 text-[11px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Voltar ao App
        </Link>

        <div class="flex items-center gap-2 group relative">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-[10px] text-gray-400">Sistema Online</span>
        </div>

        <div class="relative group">
             <Dropdown align="right" width="64">
                <template #trigger>
                    <button class="p-1 text-gray-400 hover:text-primary-600 transition flex items-center">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </button>
                </template>
                <template #content>
                    <div class="px-4 py-2 text-xs text-gray-500 font-bold border-b border-gray-100 uppercase">
                        Alertas de Gestao
                    </div>
                    <div class="px-4 py-6 text-center text-xs text-gray-400 lowercase font-normal italic">
                        Sem alertas criticos no momento.
                    </div>
                </template>
             </Dropdown>
        </div>

        <Dropdown align="right" width="64">
            <template #trigger>
                <button class="flex items-center gap-2 hover:opacity-80 transition cursor-pointer bg-primary-600 text-white px-3 py-1.5 rounded-lg shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span class="text-[11px]">Staff: {{ $page.props.auth.user.name.split(' ')[0] }}</span>
                    <svg class="w-3 h-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </template>

            <template #content>
                <div class="block px-4 py-2 text-xs text-gray-400 border-b border-gray-100 truncate">
                    LOGADO COMO ADMINISTRADOR
                </div>

                <DropdownLink :href="route('profile.edit')" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Meu Perfil
                </DropdownLink>

                <DropdownLink :href="route('admin.logout')" method="post" as="button" class="flex items-center gap-2 text-red-600 hover:text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Encerrar Sessao
                </DropdownLink>
            </template>
        </Dropdown>

        <div class="flex items-center gap-2 border-l border-gray-200 pl-4 ml-2">
            <button @click="mudarIdioma('pt')" class="hover:scale-110 transition-transform cursor-pointer hover:opacity-100" :class="idiomaAtual === 'pt' ? 'opacity-100' : 'opacity-60'" title="Portugues">
                <img src="/flags/br.svg" alt="Portugues" class="w-5 h-auto rounded-sm shadow-sm" />
            </button>
            <button @click="mudarIdioma('en')" class="hover:scale-110 transition-transform cursor-pointer hover:opacity-100" :class="idiomaAtual === 'en' ? 'opacity-100' : 'opacity-60'" title="English">
                <img src="/flags/us.svg" alt="English" class="w-5 h-auto rounded-sm shadow-sm" />
            </button>
        </div>
    </nav>
</template>
