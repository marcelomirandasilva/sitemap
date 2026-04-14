<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center rounded-lg border border-transparent bg-accent-600 px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-sm transition-all duration-200 ease-in-out hover:bg-accent-700 hover:shadow-md focus:bg-accent-700 focus:outline-none focus:ring-2 focus:ring-accent-500 focus:ring-offset-2 active:scale-95 active:bg-accent-800 disabled:pointer-events-none disabled:opacity-50">
        <slot />
    </button>
</template>
