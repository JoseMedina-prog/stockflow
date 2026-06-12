<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        data?: number[];
        height?: number;
        strokeClass?: string;
        fillClass?: string;
    }>(),
    {
        data: () => [] as number[],
        height: 40,
        strokeClass: 'stroke-teal-500 dark:stroke-teal-400',
        fillClass: 'fill-teal-500/15 dark:fill-teal-400/20',
    },
);

const width = 120;
const padding = 2;

const path = computed(() => {
    if (props.data.length < 2) return { line: '', area: '' };
    const max = Math.max(...props.data, 1);
    const min = Math.min(...props.data, 0);
    const range = max - min || 1;
    const stepX = (width - padding * 2) / (props.data.length - 1);
    const usableHeight = props.height - padding * 2;

    const points = props.data.map((v, i) => {
        const x = padding + i * stepX;
        const y = padding + usableHeight - ((v - min) / range) * usableHeight;
        return { x, y };
    });

    const line = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(2)} ${p.y.toFixed(2)}`).join(' ');

    const first = points[0];
    const last = points[points.length - 1];
    const area = `${line} L ${last.x.toFixed(2)} ${props.height - padding} L ${first.x.toFixed(2)} ${props.height - padding} Z`;

    return { line, area };
});
</script>

<template>
    <svg :width="width" :height="height" :viewBox="`0 0 ${width} ${height}`" class="overflow-visible" preserveAspectRatio="none">
        <path :d="path.area" :class="fillClass" stroke="none" />
        <path :d="path.line" :class="strokeClass" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</template>
