/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './components/**/*.{js,vue,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './plugins/**/*.{js,ts}',
    './app.vue',
  ],
  theme: {
    extend: {
      colors: {
        navy: {
          700: '#1B2559',
          800: '#111C44',
          900: '#0B1437',
        },
        brand: {
          400: '#7551FF',
          500: '#422AFB',
          600: '#3311DB',
        },
        lightPrimary: '#F4F7FE',
      },
      container: {
        center: true,
        padding: '2rem',
        screens: {
          '2xl': '1400px',
        },
      },
    },
  },
  plugins: [require('@tailwindcss/typography')],
}
