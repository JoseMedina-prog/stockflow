import vue from '@vitejs/plugin-vue';
import autoprefixer from 'autoprefixer';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import tailwindcss from 'tailwindcss';
import { defineConfig } from 'vite';

export default defineConfig(({ mode }) => ({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            refresh: mode !== 'production',
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
    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            'ziggy-js',
            'radix-vue',
            'vue-sonner',
            'lucide-vue-next',
            '@vueuse/core',
        ],
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
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('radix-vue')) return 'vendor-radix';
                        if (id.includes('lucide-vue-next')) return 'vendor-lucide';
                        if (id.includes('@vueuse/core')) return 'vendor-vueuse';
                        if (id.includes('@inertiajs/inertia') || id.includes('ziggy-js')) return 'vendor-inertia';
                        if (id.includes('@vue/runtime') || id.includes('/vue/') || id.includes('node_modules/vue')) return 'vendor-vue';
                    }
                },
            },
        },
    },
}));
