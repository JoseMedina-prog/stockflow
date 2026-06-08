<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    variant?: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning';
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-semibold transition-colors',
                {
                    'border-transparent bg-primary text-primary-foreground': variant === 'default',
                    'border-transparent bg-secondary text-secondary-foreground': variant === 'secondary',
                    'border-transparent bg-destructive text-destructive-foreground': variant === 'destructive',
                    'text-foreground': variant === 'outline',
                    'border-transparent bg-emerald-500 text-white': variant === 'success',
                    'border-transparent bg-amber-500 text-white': variant === 'warning',
                },
                props.class,
            )
        "
    >
        <slot />
    </span>
</template>
