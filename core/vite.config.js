import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', // your main CSS file
                'resources/js/app.js',   // your main JS file
            ],
            refresh: true,
        }),
    ],
    server: {
        cors: true,
        host: 'localhost',
        port: 5173
    }
});
