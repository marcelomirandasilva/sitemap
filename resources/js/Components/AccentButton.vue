<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center px-6 py-2.5 bg-accent-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-wider hover:bg-accent-700 focus:bg-accent-700 active:bg-accent-800 focus:outline-none focus:ring-2 focus:ring-accent-500 focus:ring-offset-2 transition-all duration-200 ease-in-out shadow-sm hover:shadow-md active:scale-95 disabled:opacity-50 disabled:pointer-events-none">
        <slot />
    </button>
</template>