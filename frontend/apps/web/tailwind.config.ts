import type { Config } from 'tailwindcss'
import { baseConfig } from '@repo/config/tailwind'

const config: Config = {
  ...baseConfig,
  content: [
    './app/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
    './lib/**/*.{ts,tsx}',
  ],
} satisfies Config

export default config
