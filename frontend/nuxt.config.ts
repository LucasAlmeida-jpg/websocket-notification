export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  modules: ['@pinia/nuxt'],

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE ?? 'http://localhost:8080',
      pusherKey: process.env.NUXT_PUBLIC_PUSHER_KEY ?? 'local-key',
      pusherHost: process.env.NUXT_PUBLIC_PUSHER_HOST ?? '127.0.0.1',
      pusherPort: process.env.NUXT_PUBLIC_PUSHER_PORT ?? '6001',
      pusherCluster: process.env.NUXT_PUBLIC_PUSHER_CLUSTER ?? 'mt1',
    },
  },

  routeRules: {
    '/api/**': { proxy: process.env.NUXT_PUBLIC_API_BASE ?? 'http://localhost:8080/api/**' },
  },
})
