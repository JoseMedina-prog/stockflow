<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

const { appearance, updateAppearance } = useAppearance();

const nextLabel = computed(() => {
    if (appearance.value === 'light') return 'Cambiar a tema oscuro';
    if (appearance.value === 'dark') return 'Usar tema del sistema';
    return 'Cambiar a tema claro';
});

const cycleTheme = () => {
    if (appearance.value === 'light') updateAppearance('dark');
    else if (appearance.value === 'dark') updateAppearance('system');
    else updateAppearance('light');
};
</script>

<template>
    <button
        type="button"
        :title="nextLabel"
        :aria-label="nextLabel"
        class="flex size-9 items-center justify-center rounded-md text-muted-foreground transition-all duration-200 hover:bg-accent hover:text-foreground active:scale-95"
        @click="cycleTheme"
    >
        <Transition mode="out-in" enter-active-class="transition-all duration-200" enter-from-class="rotate-90 opacity-0" leave-active-class="transition-all duration-200" leave-to-class="-rotate-90 opacity-0">
            <Sun v-if="appearance === 'light'" class="size-4" />
            <Moon v-else-if="appearance === 'dark'" class="size-4" />
            <Monitor v-else class="size-4" />
        </Transition>
    </button>
</template>
