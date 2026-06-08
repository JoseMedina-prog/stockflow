<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    from: string;
    to: string;
    routeName: string;
    extra?: Record<string, string | number | null | undefined>;
}>();

const fromInput = ref(props.from);
const toInput = ref(props.to);

watch([fromInput, toInput], () => {
    router.get(
        route(props.routeName),
        { from: fromInput.value, to: toInput.value, ...(props.extra ?? {}) },
        { preserveScroll: true, preserveState: true },
    );
});
</script>

<template>
    <div class="flex flex-wrap items-end gap-2 rounded-lg border border-border/60 bg-muted/30 p-3">
        <div class="flex flex-col gap-1">
            <label class="text-xs text-muted-foreground">Desde</label>
            <Input v-model="fromInput" type="date" class="h-9 w-40" />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs text-muted-foreground">Hasta</label>
            <Input v-model="toInput" type="date" class="h-9 w-40" />
        </div>
        <slot />
    </div>
</template>
