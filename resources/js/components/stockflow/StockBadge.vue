<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { computed } from 'vue';

const props = defineProps<{
    stock: number;
    minStock: number;
}>();

const variant = computed<'destructive' | 'warning' | 'secondary'>(() => {
    if (props.stock === 0) return 'destructive';
    if (props.stock <= props.minStock) return 'warning';
    return 'secondary';
});

const label = computed(() => {
    if (props.stock === 0) return 'Sin stock';
    if (props.stock <= props.minStock) return 'Stock bajo';
    return 'OK';
});
</script>

<template>
    <div class="flex items-center gap-2">
        <Badge :variant="variant">{{ label }}</Badge>
        <span class="text-sm tabular-nums">{{ stock }} / {{ minStock }}</span>
    </div>
</template>
