import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface UseDeleteActionOptions {
    routeName: string;
    paramKey?: string;
    preserveScroll?: boolean;
}

export function useDeleteAction(options: UseDeleteActionOptions) {
    const confirmOpen = ref(false);
    const processing = ref(false);
    const target = ref<{ id: number | string } | null>(null);

    const ask = (item: { id: number | string }) => {
        target.value = item;
        confirmOpen.value = true;
    };

    const cancel = () => {
        confirmOpen.value = false;
        target.value = null;
    };

    const handle = () => {
        if (!target.value) return;

        const paramKey = options.paramKey ?? 'id';
        processing.value = true;

        router.delete(route(options.routeName, { [paramKey]: target.value.id }), {
            preserveScroll: options.preserveScroll ?? true,
            onFinish: () => {
                processing.value = false;
                confirmOpen.value = false;
                target.value = null;
            },
        });
    };

    return { confirmOpen, processing, target, ask, cancel, handle };
}
