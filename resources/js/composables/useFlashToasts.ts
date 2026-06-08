import { usePage } from '@inertiajs/vue3';
import { nextTick, onMounted, watch } from 'vue';
import { toast } from 'vue-sonner';

interface Flash {
    success?: string;
    error?: string;
}

const lastShown: { success?: string; error?: string } = {};

export function useFlashToasts() {
    const page = usePage();

    onMounted(() => {
        nextTick(() => {
            watch(
                () => (page.props as { flash?: Flash }).flash ?? {},
                (flash) => {
                    if (flash.success && flash.success !== lastShown.success) {
                        lastShown.success = flash.success;
                        toast.success(flash.success);
                    }
                    if (flash.error && flash.error !== lastShown.error) {
                        lastShown.error = flash.error;
                        toast.error(flash.error);
                    }
                },
                { immediate: true, deep: true },
            );
        });
    });
}
