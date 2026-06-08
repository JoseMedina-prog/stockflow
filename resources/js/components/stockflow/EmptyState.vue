<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Inbox } from 'lucide-vue-next';
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        icon?: Component;
        title: string;
        description?: string;
        action?: {
            label: string;
            href: string;
        };
    }>(),
    { icon: () => Inbox },
);
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border/80 bg-muted/20 px-6 py-12 text-center">
        <div class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
            <component :is="icon" class="size-6" />
        </div>
        <div>
            <h3 class="text-sm font-semibold">{{ title }}</h3>
            <p v-if="description" class="mt-1 max-w-sm text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>
        <Button v-if="action" as-child size="sm">
            <Link :href="action.href">{{ action.label }}</Link>
        </Button>
        <slot />
    </div>
</template>
