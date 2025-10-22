import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        // Optimize build output with esbuild (faster and built-in)
        minify: 'esbuild',
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs'],
                },
            },
        },
        // Optimize chunk size
        chunkSizeWarningLimit: 600,
        // Enable CSS code splitting
        cssCodeSplit: true,
        // Disable source maps for smaller build size
        sourcemap: false,
        // Target modern browsers for smaller builds
        target: 'es2020',
    },
    esbuild: {
        // Drop console and debugger in production
        drop: ['console', 'debugger'],
    },
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: false,
        hmr: {
            host: 'localhost',
            protocol: 'ws',
        },
        cors: true,
        watch: {
            usePolling: true,
        },
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['alpinejs'],
    },
});
