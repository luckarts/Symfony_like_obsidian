import { expect, test } from '@playwright/test'
import { deleteTestUser } from '../helpers/api'
import { MailpitHelper } from '../helpers/mailpit'

test.describe('@email Email verification', () => {
  let mailpit: MailpitHelper

  test.beforeEach(async () => {
    mailpit = new MailpitHelper()
    await mailpit.clearMessages()
  })

  test('happy path — signup sends verification email', async ({ page }) => {
    test.skip(!!process.env.CI, 'Backend Symfony non disponible en CI')

    const email = `e2e-verify-${Date.now()}@test.com`

    await page.goto('/signup')
    await page.waitForLoadState('networkidle')
    await page.getByRole('textbox', { name: 'Prenom' }).fill('E2E')
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('Verify')
    await page.getByRole('textbox', { name: 'Email' }).fill(email)
    await page.getByRole('textbox', { name: 'Mot de passe' }).fill('E2eStr0ng!Pass')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()

    await expect(page.getByRole('alert').filter({ hasText: 'Bienvenue !' })).toBeVisible()

    const verificationEmail = await mailpit.waitForMessage(
      (msg) => msg.To.some((to) => to.Address === email),
      15000
    )

    expect(verificationEmail).not.toBeNull()
    expect(verificationEmail?.Subject).toContain('confirm')
    expect(verificationEmail?.To[0].Address).toBe(email)

    await deleteTestUser(email)
  })

  test('happy path — verification link confirms email', async ({ page }) => {
    test.skip(!!process.env.CI, 'Backend Symfony non disponible en CI')

    const email = `e2e-verify-${Date.now()}@test.com`

    await page.goto('/signup')
    await page.waitForLoadState('networkidle')
    await page.getByRole('textbox', { name: 'Prenom' }).fill('E2E')
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('Verify')
    await page.getByRole('textbox', { name: 'Email' }).fill(email)
    await page.getByRole('textbox', { name: 'Mot de passe' }).fill('E2eStr0ng!Pass')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()

    await expect(page.getByRole('alert').filter({ hasText: 'Bienvenue !' })).toBeVisible()

    const verificationEmail = await mailpit.waitForMessage(
      (msg) => msg.To.some((to) => to.Address === email),
      15000
    )

    expect(verificationEmail).not.toBeNull()

    const signedUrl = mailpit.extractLink(
      verificationEmail!,
      /(https?:\/\/[^\s"'<>]+\/api\/email\/verify[^\s"'<>]*)/i
    )

    expect(signedUrl).not.toBeNull()

    const response = await page.goto(signedUrl!)
    expect(response?.status()).toBe(200)

    const body = await response?.json()
    expect(body.message).toBe('Email verified successfully.')

    await deleteTestUser(email)
  })

  test('email structure — contains verification link', async ({ page }) => {
    test.skip(!!process.env.CI, 'Backend Symfony non disponible en CI')

    const email = `e2e-verify-${Date.now()}@test.com`

    await page.goto('/signup')
    await page.waitForLoadState('networkidle')
    await page.getByRole('textbox', { name: 'Prenom' }).fill('E2E')
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('Verify')
    await page.getByRole('textbox', { name: 'Email' }).fill(email)
    await page.getByRole('textbox', { name: 'Mot de passe' }).fill('E2eStr0ng!Pass')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()

    const verificationEmail = await mailpit.waitForMessage(
      (msg) => msg.To.some((to) => to.Address === email),
      15000
    )

    expect(verificationEmail).not.toBeNull()
    expect(verificationEmail?.HTML).toContain('/api/email/verify')
    expect(verificationEmail?.HTML).toContain('userId')
    expect(verificationEmail?.HTML).toContain('Confirm my email')

    await deleteTestUser(email)
  })
})
