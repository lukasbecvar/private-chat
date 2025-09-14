import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
    base: './',
    plugins: [react()],
    server: {
        host: '0.0.0.0',
        port: 80
    },
    test: {
        reporters: 'verbose',
        globals: true,
        environment: 'jsdom',
        setupFiles: './tests/setupTests.js',
        include: ['tests/**/*.test.jsx'],
    },
})
