<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    href: string;
    tabindex?: number | string;
    method?: string;
    as?: string;
}

const props = defineProps<Props>();

const normalizedTabindex = computed(() => {
    if (props.tabindex === undefined || props.tabindex === null) {
        return undefined;
    }

    const value = typeof props.tabindex === 'string' ? Number(props.tabindex) : props.tabindex;

    return Number.isFinite(value) ? value : undefined;
});
</script>

<template>
    <Link
        :href="href"
        :tabindex="normalizedTabindex"
        :method="method"
        :as="as"
        class="hover:decoration-current! text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out dark:decoration-neutral-500"
    >
        <slot />
    </Link>
</template>
