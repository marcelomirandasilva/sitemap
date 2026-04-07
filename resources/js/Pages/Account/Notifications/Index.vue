<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    notificacoes: Object,
    nao_lidas: Number,
});

const itens = computed(() => props.notificacoes?.data ?? []);
const linksPaginacao = computed(() => props.notificacoes?.links ?? []);

const formatarDataHora = (valor) => {
    if (!valor) return '-';

    return new Date(valor).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const marcarComoLida = (id) => {
    router.post(route('notifications.read', { notificacaoId: id }), {}, {
        preserveScroll: true,
        only: ['notificacoes', 'nao_lidas', 'flash'],
    });
};

const marcarTodasComoLidas = () => {
    router.post(route('notifications.read-all'), {}, {
        preserveScroll: true,
        only: ['notificacoes', 'nao_lidas', 'flash'],
    });
};
</script>

<template>
    <Head :title="$t('notifications.title')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 leading-tight">{{ $t('notifications.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ $t('notifications.subtitle') }}</p>
                </div>
                <button
                    v-if="nao_lidas > 0"
                    type="button"
                    class="rounded-md border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 transition hover:bg-primary-100"
                    @click="marcarTodasComoLidas"
                >
                    {{ $t('notifications.mark_all_read') }}
                </button>
            </div>
        </template>

        <div class="space-y-4">
            <div v-if="itens.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-10 text-center text-sm text-gray-500">
                {{ $t('notifications.empty') }}
            </div>

            <article
                v-for="item in itens"
                :key="item.id"
                class="rounded-lg border bg-white px-5 py-4 shadow-sm"
                :class="item.lida ? 'border-gray-200' : 'border-primary-200 bg-primary-50/30'"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span v-if="!item.lida" class="inline-flex h-2.5 w-2.5 rounded-full bg-primary-500"></span>
                            <h3 class="text-sm font-semibold text-gray-900">{{ item.titulo }}</h3>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">{{ item.mensagem }}</p>
                        <div class="mt-3 text-xs uppercase tracking-wide text-gray-400">{{ formatarDataHora(item.criada_em) }}</div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <button
                            v-if="!item.lida"
                            type="button"
                            class="rounded-md border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 transition hover:border-primary-300 hover:text-primary-700"
                            @click="marcarComoLida(item.id)"
                        >
                            {{ $t('notifications.mark_read') }}
                        </button>
                        <Link
                            v-if="item.url"
                            :href="item.url"
                            class="rounded-md bg-primary-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-primary-700"
                        >
                            {{ $t('notifications.open') }}
                        </Link>
                    </div>
                </div>
            </article>

            <div v-if="linksPaginacao.length > 3" class="flex flex-wrap items-center gap-2 pt-2">
                <Link
                    v-for="link in linksPaginacao"
                    :key="`${link.label}-${link.url}`"
                    :href="link.url || '#'"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="[
                        link.active ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-600',
                        !link.url ? 'pointer-events-none opacity-50' : 'hover:border-primary-300 hover:text-primary-700'
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
