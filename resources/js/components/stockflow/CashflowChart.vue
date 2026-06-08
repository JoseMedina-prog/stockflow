<script setup lang="ts">
import { computed } from 'vue';
import { formatCurrency } from '@/composables/useFormat';

interface ChartPoint {
    date: string;
    label: string;
    sales: number;
    purchases: number;
    cashflow: number;
}

const props = defineProps<{
    data: ChartPoint[];
    height?: number;
}>();

const height = computed(() => props.height ?? 160);
const width = 600;
const padding = { top: 12, right: 8, bottom: 24, left: 36 };

const innerWidth = width - padding.left - padding.right;
const innerHeight = height.value - padding.top - padding.bottom;

const max = computed(() => {
    const values = props.data.flatMap((d) => [d.sales, d.purchases]);
    const m = Math.max(1, ...values);
    return Math.ceil(m / 100) * 100;
});

const barWidth = computed(() => {
    if (props.data.length === 0) return 0;
    return (innerWidth / props.data.length) * 0.7;
});

const barGap = computed(() => {
    if (props.data.length === 0) return 0;
    return (innerWidth / props.data.length) * 0.3;
});

const yTicks = computed(() => {
    const m = max.value;
    return [0, m * 0.25, m * 0.5, m * 0.75, m];
});

const linePath = computed(() => {
    if (props.data.length === 0) return '';
    return props.data
        .map((d, i) => {
            const x = padding.left + (innerWidth / props.data.length) * (i + 0.5);
            const y = padding.top + innerHeight - (d.cashflow / max.value) * innerHeight;
            return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
        })
        .join(' ');
});

const linePoints = computed(() => {
    return props.data.map((d, i) => ({
        x: padding.left + (innerWidth / props.data.length) * (i + 0.5),
        y: padding.top + innerHeight - (d.cashflow / max.value) * innerHeight,
        value: d.cashflow,
        label: d.label,
    }));
});
</script>

<template>
    <svg :viewBox="`0 0 ${width} ${height}`" class="h-full w-full" preserveAspectRatio="none">
        <g v-for="tick in yTicks" :key="tick">
            <line
                :x1="padding.left"
                :x2="width - padding.right"
                :y1="padding.top + innerHeight - (tick / max) * innerHeight"
                :y2="padding.top + innerHeight - (tick / max) * innerHeight"
                stroke="currentColor"
                stroke-opacity="0.08"
                stroke-dasharray="2 4"
            />
            <text
                :x="padding.left - 6"
                :y="padding.top + innerHeight - (tick / max) * innerHeight + 3"
                text-anchor="end"
                class="fill-muted-foreground text-[10px]"
            >
                {{ formatCurrency(tick).replace('.00', '') }}
            </text>
        </g>

        <g v-for="(d, i) in data" :key="`bar-${d.date}`">
            <rect
                :x="padding.left + (innerWidth / data.length) * i + barGap / 2"
                :y="padding.top + innerHeight - (d.sales / max) * innerHeight"
                :width="barWidth / 2"
                :height="(d.sales / max) * innerHeight"
                fill="hsl(142 76% 36% / 0.75)"
                rx="2"
            />
            <rect
                :x="padding.left + (innerWidth / data.length) * i + barGap / 2 + barWidth / 2"
                :y="padding.top + innerHeight - (d.purchases / max) * innerHeight"
                :width="barWidth / 2"
                :height="(d.purchases / max) * innerHeight"
                fill="hsl(0 84% 60% / 0.7)"
                rx="2"
            />
        </g>

        <path
            :d="linePath"
            fill="none"
            stroke="hsl(217 91% 60%)"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <g v-for="(p, i) in linePoints" :key="`pt-${i}`">
            <circle :cx="p.x" :cy="p.y" r="3" fill="hsl(217 91% 60%)" />
            <title>{{ p.label }}: {{ formatCurrency(p.value) }}</title>
        </g>

        <g v-for="(d, i) in data" :key="`label-${d.date}`">
            <text
                :x="padding.left + (innerWidth / data.length) * (i + 0.5)"
                :y="height - 6"
                text-anchor="middle"
                class="fill-muted-foreground text-[10px] uppercase"
            >
                {{ d.label }}
            </text>
        </g>
    </svg>
</template>
