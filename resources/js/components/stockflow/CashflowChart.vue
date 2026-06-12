<script setup lang="ts">
import { formatCurrency } from '@/composables/useFormat';
import { computed } from 'vue';

interface ChartPoint {
    date: string;
    label: string;
    sales: number;
    purchases: number;
    cashflow: number;
}

const props = withDefaults(
    defineProps<{
        data?: ChartPoint[];
        height?: number;
    }>(),
    {
        data: () => [] as ChartPoint[],
    },
);

const height = computed(() => props.height ?? 160);
const width = 600;
const padding = { top: 16, right: 16, bottom: 28, left: 64 };

const innerWidth = width - padding.left - padding.right;
const innerHeight = computed(() => height.value - padding.top - padding.bottom);

const max = computed(() => {
    if (props.data.length === 0) return 100;
    const values = props.data.flatMap((d) => [d.sales, d.purchases, Math.abs(d.cashflow)]);
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
            const y = padding.top + innerHeight.value - (d.cashflow / max.value) * innerHeight.value;
            return `${i === 0 ? 'M' : 'L'} ${x} ${y}`;
        })
        .join(' ');
});

const linePoints = computed(() => {
    return props.data.map((d, i) => ({
        x: padding.left + (innerWidth / props.data.length) * (i + 0.5),
        y: padding.top + innerHeight.value - (d.cashflow / max.value) * innerHeight.value,
        value: d.cashflow,
        label: d.label,
    }));
});

const yForTick = (tick: number) => padding.top + innerHeight.value - (tick / max.value) * innerHeight.value;
const xForBar = (i: number) => padding.left + (innerWidth / props.data.length) * i;
const xForCenter = (i: number) => padding.left + (innerWidth / props.data.length) * (i + 0.5);
</script>

<template>
    <svg :viewBox="`0 0 ${width} ${height}`" class="h-full w-full" preserveAspectRatio="none" role="img" aria-label="Gráfico de flujo de caja">
        <g v-for="tick in yTicks" :key="`grid-${tick}`">
            <line
                :x1="padding.left"
                :x2="width - padding.right"
                :y1="yForTick(tick)"
                :y2="yForTick(tick)"
                stroke="currentColor"
                stroke-opacity="0.14"
                stroke-dasharray="2 4"
            />
            <text
                :x="padding.left - 10"
                :y="yForTick(tick) + 4"
                text-anchor="end"
                fill="hsl(var(--muted-foreground))"
                style="font-size: 11px; font-weight: 500; font-family: inherit"
            >
                {{ formatCurrency(tick).replace('.00', '') }}
            </text>
        </g>

        <g v-for="(d, i) in data" :key="`bar-${d.date}`">
            <rect
                :x="xForBar(i) + barGap / 2"
                :y="padding.top + innerHeight - (d.sales / max) * innerHeight"
                :width="barWidth / 2 - 1"
                :height="(d.sales / max) * innerHeight"
                fill="hsl(142 71% 45% / 0.85)"
                rx="2"
            />
            <rect
                :x="xForBar(i) + barGap / 2 + barWidth / 2 + 1"
                :y="padding.top + innerHeight - (d.purchases / max) * innerHeight"
                :width="barWidth / 2 - 1"
                :height="(d.purchases / max) * innerHeight"
                fill="hsl(0 72% 55% / 0.8)"
                rx="2"
            />
        </g>

        <path
            v-if="data.length > 0"
            :d="linePath"
            fill="none"
            stroke="hsl(var(--primary))"
            stroke-width="2.25"
            stroke-linecap="round"
            stroke-linejoin="round"
        />

        <g v-for="(p, i) in linePoints" :key="`pt-${i}`">
            <circle :cx="p.x" :cy="p.y" r="3.5" fill="hsl(var(--primary))" stroke="hsl(var(--card))" stroke-width="1.5" />
            <title>{{ p.label }}: {{ formatCurrency(p.value) }}</title>
        </g>

        <g v-for="(d, i) in data" :key="`label-${d.date}`">
            <text
                :x="xForCenter(i)"
                :y="height - 10"
                text-anchor="middle"
                fill="hsl(var(--muted-foreground))"
                style="font-size: 11px; font-weight: 500; font-family: inherit"
            >
                {{ d.label }}
            </text>
        </g>
    </svg>
</template>
