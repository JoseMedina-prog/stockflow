<script setup lang="ts">
import { useFlashToasts } from '@/composables/useFlashToasts';

useFlashToasts();
</script>

<template>
    <slot />
</template>
