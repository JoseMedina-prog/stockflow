<script setup lang="ts">
import StockFlowLogoIcon from '@/components/StockFlowLogoIcon.vue';

interface Props {
    class?: string;
    showText?: boolean;
}

withDefaults(defineProps<Props>(), {
    showText: true,
});
</script>

<template>
    <div class="flex items-center gap-2.5">
        <div class="flex aspect-square size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm">
            <StockFlowLogoIcon class-name="size-5" />
        </div>
        <span v-if="showText" class="grid text-base font-bold leading-none tracking-tight">
            <span>StockFlow</span>
        </span>
    </div>
</template>
