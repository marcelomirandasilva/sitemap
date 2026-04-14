<script setup>
defineOptions({
    inheritAttrs: false,
});

defineProps({
    alt: {
        type: String,
        default: 'Sitemap Gen',
    },
});
</script>

<template>
    <img src="/logo.png" :alt="alt" v-bind="$attrs" />
</template>
