const apiBase = process.env.NUXT_PUBLIC_API_BASE ?? 'http://localhost:8000'

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: ['@pinia/nuxt'],

  vite: {
    plugins: [
      (await import('@tailwindcss/vite')).default(),
    ],
  },

  runtimeConfig: {
    public: {
      apiBase,
      pusherKey: process.env.NUXT_PUBLIC_PUSHER_KEY ?? 'local-key',
      pusherHost: process.env.NUXT_PUBLIC_PUSHER_HOST ?? '127.0.0.1',
      pusherPort: process.env.NUXT_PUBLIC_PUSHER_PORT ?? '6001',
      pusherCluster: process.env.NUXT_PUBLIC_PUSHER_CLUSTER ?? 'mt1',
    },
  },

  css: ['~/assets/css/main.css'],

  nitro: {
    devProxy: {
      '/api': { target: `${apiBase}/api`, changeOrigin: true, prependPath: true },
      '/broadcasting': { target: `${apiBase}/broadcasting`, changeOrigin: true, prependPath: true },
    },
  },
})
