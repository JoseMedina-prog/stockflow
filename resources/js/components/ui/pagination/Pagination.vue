<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    links: PaginationLink[];
}>();

const visibleLinks = computed(() =>
    props.links.filter(
        (link) =>
            link.url !== null ||
            link.label.includes('Previous') ||
            link.label.includes('Next'),
    ),
);
</script>

<template>
    <nav v-if="links.length > 1" class="flex flex-wrap items-center justify-center gap-1">
        <template v-for="(link, index) in visibleLinks" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                :class="[
                    'rounded-md border px-3 py-1 text-sm transition-colors',
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-input bg-background hover:bg-accent hover:text-accent-foreground',
                ]"
                v-html="link.label"
            />
            <span
                v-else
                class="rounded-md border border-input bg-muted px-3 py-1 text-sm text-muted-foreground opacity-50"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
