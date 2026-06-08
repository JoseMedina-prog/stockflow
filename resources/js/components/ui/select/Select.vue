<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    modelValue: string | number | null;
    class?: HTMLAttributes['class'];
}>();

defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();
</script>

<template>
    <select
        :value="modelValue ?? ''"
        :class="
            cn(
                'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
                props.class,
            )
        "
        @change="
            $emit(
                'update:modelValue',
                ($event.target as HTMLSelectElement).value || null,
            )
        "
    >
        <slot />
    </select>
</template>
