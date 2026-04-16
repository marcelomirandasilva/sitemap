<script setup>
import { Link } from '@inertiajs/vue3';

const itemClass = (padrao) => route().current(padrao)
    ? 'px-4 py-2 border-b-2 border-primary-500 text-primary-600 font-medium pb-2 -mb-2'
    : 'px-4 py-2 text-gray-500 hover:text-gray-700 font-medium';
</script>

<template>
    <div class="flex space-x-4 mb-8 border-b border-gray-200 dark:border-gray-700 pb-2 overflow-x-auto">
        <Link :href="route('admin.dashboard')" :class="itemClass('admin.dashboard')">Painel Executivo</Link>
        <Link :href="route('admin.users.index')" :class="itemClass('admin.users.*')">Usuarios</Link>
        <Link :href="route('admin.plans.index')" :class="itemClass('admin.plans.*')">Planos</Link>
        <Link :href="route('admin.changelog.index')" :class="itemClass('admin.changelog.*')">Changelog</Link>
        <Link :href="route('admin.tickets.index')" :class="itemClass('admin.tickets.*')">Suporte</Link>
        <Link :href="route('admin.jobs.index')" :class="itemClass('admin.jobs.*')">Motor Crawler</Link>
    </div>
</template>
