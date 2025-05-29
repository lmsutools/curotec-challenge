<script setup>
import { ref } from 'vue';
defineProps({
    modelValue: [String, Number],
    options: Array,
});
defineEmits(['update:modelValue']);
const select = ref(null);
defineExpose({ focus: () => select.value.focus() });
</script>
<template>
    <select
        ref="select"
        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
        <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</template>