<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { Component } from 'vue';

type Accent = 'primary' | 'success' | 'warning' | 'destructive' | 'info' | 'purple';

const accentMap: Record<Accent, { bg: string; text: string; ring: string }> = {
    primary: { bg: 'bg-teal-500/10 dark:bg-teal-400/15', text: 'text-teal-700 dark:text-teal-300', ring: 'ring-teal-500/20' },
    success: { bg: 'bg-emerald-500/10 dark:bg-emerald-400/15', text: 'text-emerald-700 dark:text-emerald-300', ring: 'ring-emerald-500/20' },
    warning: { bg: 'bg-amber-500/10 dark:bg-amber-400/15', text: 'text-amber-700 dark:text-amber-300', ring: 'ring-amber-500/20' },
    destructive: { bg: 'bg-rose-500/10 dark:bg-rose-400/15', text: 'text-rose-700 dark:text-rose-300', ring: 'ring-rose-500/20' },
    info: { bg: 'bg-sky-500/10 dark:bg-sky-400/15', text: 'text-sky-700 dark:text-sky-300', ring: 'ring-sky-500/20' },
    purple: { bg: 'bg-violet-500/10 dark:bg-violet-400/15', text: 'text-violet-700 dark:text-violet-300', ring: 'ring-violet-500/20' },
};

withDefaults(
    defineProps<{
        title: string;
        value: string | number;
        description?: string;
        icon?: Component;
        accent?: Accent;
    }>(),
    { accent: 'primary' },
);
</script>

<template>
    <div class="rounded-xl border border-border/60 bg-card p-5 shadow-sm transition-shadow hover:shadow-md">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums tracking-tight">
                    {{ value }}
                </p>
                <p v-if="description" class="mt-1 text-xs text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <div
                v-if="icon"
                :class="
                    cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-xl ring-1',
                        accentMap[accent].bg,
                        accentMap[accent].text,
                        accentMap[accent].ring,
                    )
                "
            >
                <component :is="icon" class="size-5" />
            </div>
        </div>
        <div v-if="$slots.footer" class="mt-4">
            <slot name="footer" />
        </div>
    </div>
</template>
