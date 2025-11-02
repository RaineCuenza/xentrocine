import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: true,  // Allow external access
        port: 5173,
        strictPort: true,
        cors: {
            origin: '*',  // Allow all origins (for Cloudflare tunnel)
            credentials: true,
        },
        hmr: {
            host: 'localhost',  // HMR should connect to localhost
        },
    },
    
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
