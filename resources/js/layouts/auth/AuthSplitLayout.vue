<script setup lang="ts">
import StockFlowLogoIcon from '@/components/StockFlowLogoIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, Box, ShieldCheck, Sparkles, TrendingUp } from 'lucide-vue-next';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();

const features = [
    { icon: Box, label: 'Control de inventario en tiempo real' },
    { icon: TrendingUp, label: 'Ventas rápidas con descuento automático' },
    { icon: BarChart3, label: 'Reportes y métricas al instante' },
    { icon: ShieldCheck, label: 'Alertas de stock bajo automáticas' },
];
</script>

<template>
    <div class="relative grid min-h-svh flex-col bg-background lg:grid-cols-2">
        <div class="relative hidden flex-col overflow-hidden bg-gradient-to-br from-teal-700 via-teal-600 to-cyan-700 p-10 text-white lg:flex">
            <div class="pointer-events-none absolute inset-0 opacity-20">
                <svg class="absolute inset-0 size-full" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <defs>
                        <pattern id="grid-pattern" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="0.5" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid-pattern)" />
                </svg>
            </div>

            <div class="pointer-events-none absolute -left-20 top-1/3 size-72 rounded-full bg-cyan-400/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -right-20 bottom-1/4 size-80 rounded-full bg-teal-300/20 blur-3xl"></div>
            <div class="pointer-events-none absolute left-1/2 top-1/2 size-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/5 blur-3xl"></div>

            <Link :href="route('home')" class="relative z-10 flex items-center gap-3 text-lg font-semibold">
                <div class="flex size-10 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm ring-1 ring-white/20">
                    <StockFlowLogoIcon class-name="size-6" />
                </div>
                {{ name }}
            </Link>

            <div class="relative z-10 mt-auto">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-teal-50 backdrop-blur-sm">
                    <Sparkles class="size-3.5" />
                    Plataforma todo-en-uno
                </div>

                <h2 class="text-balance text-3xl font-bold leading-tight tracking-tight xl:text-4xl">
                    Controla tu inventario y ventas desde un solo lugar.
                </h2>
                <p class="mt-3 max-w-md text-pretty text-base text-teal-50/80">
                    Una plataforma simple y profesional para tiendas y negocios pequeños que quieren dejar de improvisar.
                </p>

                <ul class="mt-8 space-y-3">
                    <li v-for="feature in features" :key="feature.label" class="flex items-center gap-3 text-sm text-teal-50/90">
                        <span class="flex size-7 items-center justify-center rounded-md bg-white/10 ring-1 ring-white/15">
                            <component :is="feature.icon" class="size-3.5" />
                        </span>
                        {{ feature.label }}
                    </li>
                </ul>

                <p class="mt-10 text-xs text-teal-100/60">
                    © {{ new Date().getFullYear() }} {{ name }}. Hecho con Laravel + Vue.
                </p>
            </div>
        </div>

        <div class="flex flex-col p-6 sm:p-10">
            <div class="lg:hidden mb-8">
                <Link :href="route('home')" class="inline-flex items-center gap-2.5 text-base font-semibold">
                    <div class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-sm">
                        <StockFlowLogoIcon class-name="size-5" />
                    </div>
                    {{ name }}
                </Link>
            </div>

            <div class="flex flex-1 items-center justify-center">
                <div class="w-full max-w-sm">
                    <Transition
                        mode="out-in"
                        enter-active-class="transition-opacity duration-150 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity duration-100 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div :key="$page.url">
                            <div class="mb-8 flex flex-col gap-2 text-center">
                                <h1 v-if="title" class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                                <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
                            </div>
                            <slot />
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </div>
</template>
