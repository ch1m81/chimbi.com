import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/js/app.js', 'resources/css/app.css'],
      refresh: true,
    }),
    vue(),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    cors: true,
    allowedHosts: ['chimbi.local', '192.168.178.180'],
    hmr: {
      host: '192.168.178.180',
    },
  },
})
