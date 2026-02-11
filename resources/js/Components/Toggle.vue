<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Boolean, Number],
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const proxyChecked = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit('update:modelValue', val);
    },
});
</script>

<template>
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="checkbox" v-model="proxyChecked" class="sr-only peer">
        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
    </label>
</template>