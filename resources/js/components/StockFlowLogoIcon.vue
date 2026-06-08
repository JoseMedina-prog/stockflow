<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" :class="className" v-bind="$attrs" fill="none">
        <rect x="2" y="9" width="28" height="20" rx="3" fill="currentColor" fill-opacity="0.18" stroke="currentColor" stroke-width="2" />
        <path d="M5 9 L16 3 L27 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M5 9 L5 29" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <path d="M27 9 L27 29" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <path d="M11 19 L14.5 22.5 L21 16" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</template>
