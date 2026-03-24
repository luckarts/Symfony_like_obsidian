export default defineNuxtConfig({
  // Mode de développement
  devtools: { enabled: true },

  // TypeScript strict
  typescript: {
    strict: true,
    typeCheck: true,
  },

  // Modules
  modules: ['@pinia/nuxt', '@vueuse/nuxt', '@nuxtjs/i18n', '@nuxt/ui'],

  // Color mode — data-theme="dark" sur <html> au lieu de class="dark"
  colorMode: {
    dataValue: 'theme',
    classSuffix: '',
    storageKey: 'color-mode',
  },

  // CSS
  css: ['~/assets/styles/main.css'],

  // Auto-import des composants
  components: [
    {
      path: '~/components',
      pathPrefix: false, // Ne pas ajouter le chemin comme prefix au nom
      ignore: ['**/shadcn/**'], // Exclure shadcn pour forcer les imports explicites
    },
  ],

  // Variables d'environnement
  runtimeConfig: {
    // Privé (serveur seulement)
    apiSecret: '',
    // Public (client + serveur)
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || '',
    },
  },

  // Route rules (ISR, SSR, SPA)
  routeRules: {
    '/': { isr: 3600 }, // Incremental Static Regeneration
    '/admin/**': { ssr: false }, // SPA pour admin
    '/api/**': {
      proxy: { to: `${process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000'}/api/**` },
    },
  },

  // App config
  app: {
    head: {
      charset: 'utf-8',
      viewport: 'width=device-width, initial-scale=1',
      meta: [{ name: 'description', content: 'Task Manager' }],
      link: [{ rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' }],
    },
  },

  // Nitro (server)
  nitro: {
    compressPublicAssets: true,
    preset: 'node-server', // ou 'vercel', 'netlify', etc.
  },

  // Build
  build: {
    transpile: [],
  },

  // Internationalisation
  i18n: {
    strategy: 'prefix_except_default',
    defaultLocale: 'fr',
    bundle: {
      optimizeTranslationDirective: false, // Disable deprecated feature
    },
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'i18n_redirected',
      redirectOn: 'root',
    },
    locales: [
      {
        code: 'fr',
        iso: 'fr-FR',
        file: 'fr-FR.ts',
        name: 'Français',
      },
      {
        code: 'en',
        iso: 'en-US',
        file: 'en-US.ts',
        name: 'English',
      },
    ],
    langDir: 'locales',
  },

  // Expérimental
  experimental: {
    payloadExtraction: false,
    viewTransition: true,
  },

  compatibilityDate: '2025-01-17',
})
