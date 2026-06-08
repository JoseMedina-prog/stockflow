<script setup lang="ts">
import { Toaster as Sonner, type ToasterProps } from 'vue-sonner';
import { useAppearance } from '@/composables/useAppearance';

const { appearance } = useAppearance();
</script>

<template>
    <Sonner
        :theme="appearance === 'dark' ? 'dark' : 'light'"
        class="toaster group"
        :toast-options="{
            classes: {
                toast: 'group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg',
                description: 'group-[.toast]:text-muted-foreground',
                actionButton: 'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground',
                cancelButton: 'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground',
            },
        }"
        position="bottom-right"
        :duration="4000"
        :visible-toasts="5"
    />
</template>
