import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    // comment this out if php artisan serve(local), pero if via LAN use this
    server: {
        host: true, // Allow LAN access
        port: 5173, // Default Vite port
        strictPort: true,
        origin: 'http://192.168.0.197:5173',
        cors: true, // Ensure CORS headers are sent for module scripts when loaded from Laravel at 8000
    },
    // end comment
    
    plugins: [
        laravel({
            input: [
                'resources/css/styles.css',
                'resources/js/app-api.js'
            ],
            refresh: true,
        }),
    ],
});
