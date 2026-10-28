import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [laravel({ input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/school-dashboard.css', 'resources/js/school-dashboard.js'], refresh: true }), tailwindcss()],
});
