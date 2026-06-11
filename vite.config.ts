import vue from '@vitejs/plugin-vue';
import autoprefixer from 'autoprefixer';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import tailwindcss from 'tailwindcss';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
    server: {
        host: 'localhost',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
    css: {
        postcss: {
            plugins: [tailwindcss, autoprefixer],
        },
    },
    build: {
        target: 'es2020',
        cssCodeSplit: true,
        minify: 'esbuild',
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', '@vue/runtime-core', '@vue/runtime-dom', '@vue/reactivity', '@vue/shared'],
                    'vendor-inertia': ['@inertiajs/vue3', 'ziggy-js'],
                    'vendor-radix': ['radix-vue'],
                    'vendor-vueuse': ['@vueuse/core'],
                    'vendor-lucide': ['lucide-vue-next'],
                },
            },
        },
    },
});
