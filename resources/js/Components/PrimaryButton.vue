<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    processing: {
        type: Boolean,
        default: false,
    }
});
</script>

<template>
    <button 
        :type="type" 
        :disabled="processing"
        class="inline-flex items-center justify-center rounded-lg border border-transparent bg-brand-primary px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-sm transition-all duration-200 ease-in-out hover:bg-primary-700 hover:shadow-md focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:scale-95 active:bg-primary-800 disabled:pointer-events-none disabled:opacity-50"
    >
        <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>

        <slot />
    </button>
</template>
