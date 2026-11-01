import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/public.css', 'resources/js/public.js', 'resources/js/editor.js', 'resources/js/preview.js', 'resources/css/home.css', 'resources/js/home.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
