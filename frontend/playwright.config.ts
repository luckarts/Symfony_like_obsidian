import { defineConfig, devices } from '@playwright/test'

/**
 * Playwright configuration for Nuxt 3 e-commerce application
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './tests/e2e',

  // Maximum time one test can run for
  timeout: 30 * 1000,

  // Test execution settings
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,

  // Output directories
  outputDir: 'test-results',

  // Preserve output only for failed tests
  preserveOutput: 'failures-only',

  // Reporter configuration
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['list'],
  ],

  use: {
    // Base URL for your Nuxt app
    baseURL: process.env.BASE_URL || 'http://localhost:3000',

    // Collect trace when retrying the failed test
    trace: 'on-first-retry',

    // Screenshot on failure
    screenshot: 'only-on-failure',

    // Video on failure
    video: 'retain-on-failure',

    // Emulate timezone and locale
    timezoneId: 'Europe/Paris',
    locale: 'fr-FR',
  },

  // Configure projects for test types
  projects: [
    // 🔥 Smoke Tests - Chromium (Default)
    {
      name: 'smoke-tests',
      testMatch: /smoke\/.*\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
      },
      retries: 0, // No retries for smoke tests
      timeout: 30000, // 30s max per test
    },

    // 🔥 Smoke Tests - Firefox
    {
      name: 'smoke-tests-firefox',
      testMatch: /smoke\/.*\.spec\.ts/,
      use: {
        ...devices['Desktop Firefox'],
        // Firefox peut être plus lent avec les composants Radix
        actionTimeout: 15000, // 15s pour chaque action (au lieu de 10s par défaut)
      },
      retries: 0,
      timeout: 45000, // 45s max per test (au lieu de 30s)
    },

    // 🔥 Smoke Tests - WebKit (Safari)
    {
      name: 'smoke-tests-webkit',
      testMatch: /smoke\/.*\.spec\.ts/,
      use: {
        ...devices['Desktop Safari'],
      },
      retries: 0,
      timeout: 30000,
    },

    // 🔄 Regression Tests - Complete test suite
    {
      name: 'regression-tests',
      testMatch: /regression\/.*\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
      },
      retries: 1, // Allow one retry
      timeout: 60000, // 60s max per test
    },

    // 🚧 Feature Tests - Tests temporaires pour features en développement
    // ⚠️ À MIGRER vers smoke/ ou regression/ avant merge PR
    {
      name: 'feature-tests',
      testMatch: /features\/.*\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
      },
      retries: 2, // Plus de retries acceptables (WIP)
      timeout: 120000, // 2 min max par test (pas de contrainte)
    },
  ],

  // Run your local dev server before starting the tests
  // In Docker (CI=true), don't start a new server - use the existing nuxt container
  webServer: process.env.CI || process.env.PLAYWRIGHT_START_SERVER === 'false'
    ? undefined
    : {
        command: 'pnpm dev',
        url: 'http://localhost:3000',
        reuseExistingServer: true,
        timeout: 120 * 1000,
      },
})
