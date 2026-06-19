import { getRequestConfig } from 'next-intl/server'
import { notFound } from 'next/navigation'

const locales = ['fr', 'en']

export default getRequestConfig(async ({ locale }) => {
  if (!locales.includes(locale as string)) notFound()

  const localeMap: Record<string, string> = {
    fr: 'fr-FR',
    en: 'en-US',
  }

  const messages = (
    await import(`@repo/shared/i18n/locales/${localeMap[locale as string]}`)
  ).default

  return { messages }
})
