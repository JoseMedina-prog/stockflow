<script setup lang="ts">
import StockFlowLogoIcon from '@/components/StockFlowLogoIcon.vue';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground shadow-sm">
            <StockFlowLogoIcon class-name="size-5" />
        </div>
        <div class="ml-0.5 grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-semibold">StockFlow</span>
        </div>
    </div>
</template>
