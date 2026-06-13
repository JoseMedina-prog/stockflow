<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Flash {
    success?: string | null;
    error?: string | null;
}

interface PageProps {
    flash?: Flash;
    [key: string]: unknown;
}

const page = usePage<PageProps>();
const props = computed<PageProps>(() => page.props ?? ({} as PageProps));
const flash = computed<Flash>(() => props.value.flash ?? {});

interface ActiveToast {
    id: number;
    type: 'success' | 'error';
    message: string;
}

const toasts = ref<ActiveToast[]>([]);
let nextId = 1;
const timers = new Map<number, ReturnType<typeof setTimeout>>();

const dismiss = (id: number) => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
    const t = timers.get(id);
    if (t) {
        clearTimeout(t);
        timers.delete(id);
    }
};

const push = (type: 'success' | 'error', message: string) => {
    const id = nextId++;
    toasts.value = [...toasts.value, { id, type, message }];
    timers.set(
        id,
        setTimeout(() => dismiss(id), 5000),
    );
};

watch(
    () => [flash.value.success, flash.value.error],
    ([success, error]) => {
        if (success) {
            push('success', success);
        } else if (error) {
            push('error', error);
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center gap-2 px-4 pb-4 sm:bottom-6 sm:items-center sm:px-6"
            aria-live="polite"
        >
            <TransitionGroup
                tag="div"
                class="flex w-full flex-col items-center gap-2"
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-3 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in absolute"
                leave-from-class="opacity-100"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-lg border bg-card px-4 py-3 shadow-xl ring-1 ring-black/5 dark:ring-white/10"
                    :class="t.type === 'success' ? 'border-emerald-200/80 dark:border-emerald-800/60' : 'border-destructive/40'"
                    role="status"
                >
                    <component
                        :is="t.type === 'success' ? CheckCircle2 : AlertCircle"
                        class="mt-0.5 size-5 shrink-0"
                        :class="t.type === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-destructive'"
                    />
                    <p class="flex-1 text-sm font-medium leading-5 text-foreground">{{ t.message }}</p>
                    <button
                        type="button"
                        class="rounded-md p-1 text-muted-foreground transition hover:bg-muted hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                        aria-label="Cerrar notificación"
                        @click="dismiss(t.id)"
                    >
                        <X class="size-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
