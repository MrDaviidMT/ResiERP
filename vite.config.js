import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/imprimir.css', 'resources/js/imprimir.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});