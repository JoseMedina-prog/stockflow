import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

type Filters = Record<string, string | number | boolean | null | undefined>;

interface UseIndexFiltersOptions {
    initial: Filters;
    routeName: string;
    debounceMs?: number;
    preserveScroll?: boolean;
    preserveState?: boolean;
}

export function useIndexFilters(options: UseIndexFiltersOptions) {
    const values = ref<Filters>({ ...options.initial });
    let timer: ReturnType<typeof setTimeout> | null = null;

    const apply = (extra: Filters = {}) => {
        const params = { ...values.value, ...extra };
        const cleaned = Object.fromEntries(
            Object.entries(params).filter(
                ([, v]) => v !== null && v !== undefined && v !== '' && v !== false,
            ),
        );
        router.get(route(options.routeName), cleaned, {
            preserveScroll: options.preserveScroll ?? true,
            preserveState: options.preserveState ?? true,
        });
    };

    const debouncedApply = () => {
        if (timer) clearTimeout(timer);
        timer = setTimeout(apply, options.debounceMs ?? 300);
    };

    const clear = () => {
        values.value = Object.fromEntries(
            Object.entries(options.initial).map(([k]) => [k, null]),
        );
        apply();
    };

    watch(values, debouncedApply, { deep: true });

    return { values, apply, clear };
}
