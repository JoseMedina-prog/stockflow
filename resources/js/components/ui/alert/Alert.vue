<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    variant?: 'default' | 'destructive' | 'success';
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'relative w-full rounded-lg border px-4 py-3 text-sm',
                {
                    'border-border bg-card text-card-foreground': variant === 'default' || !variant,
                    'border-destructive/50 bg-destructive/10 text-destructive': variant === 'destructive',
                    'border-emerald-500/50 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300': variant === 'success',
                },
                props.class,
            )
        "
        role="alert"
    >
        <slot />
    </div>
</template>
