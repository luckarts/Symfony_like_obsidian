import { expect, test } from '@playwright/test'

test.describe('@smoke Signup', () => {
  test('happy path — signup réussi redirige vers /', async ({ page }) => {
    test.skip(!!process.env.CI, 'Backend Symfony non disponible en CI')

    const email = `e2e-signup-${Date.now()}@test.com`
    await page.goto('/signup')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Prenom').fill('E2E')
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('User')
    await page.getByRole('textbox', { name: 'Email' }).fill(email)
    await page.getByRole('textbox', { name: 'Mot de passe' }).fill('E2eStr0ng!Pass')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByRole('alert').filter({ hasText: 'Bienvenue !' })).toBeVisible()
    await expect(page).toHaveURL('/')
  })

  test('toast succès — "Bienvenue !" affiché après signup réussi', async ({ page }) => {
    await page.route('**/api/users', (route) =>
      route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          id: 'fake-uuid',
          email: 'e2e@test.com',
          firstName: 'E2E',
          lastName: 'User',
          roles: ['ROLE_USER'],
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString(),
        }),
      })
    )
    await page.goto('/signup')
    await page.waitForLoadState('networkidle')
    await page.getByRole('textbox', { name: 'Prenom' }).fill('E2E')
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('User')
    await page.getByRole('textbox', { name: 'Email' }).fill('e2e@test.com')
    await page.getByRole('textbox', { name: 'Mot de passe' }).fill('motdepasse123')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByRole('alert').filter({ hasText: 'Bienvenue !' })).toBeVisible()
  })

  test('toast erreur — 422 sans violations affiche "Email déjà utilisé"', async ({ page }) => {
    await page.route('**/api/users', (route) =>
      route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Unprocessable Entity' }),
      })
    )
    await page.goto('/signup')
    await page.waitForLoadState('networkidle')
    await page.getByRole('textbox', { name: 'Prenom' }).fill('E2E')
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('User')
    await page.getByRole('textbox', { name: 'Email' }).fill('taken@test.com')
    await page.getByRole('textbox', { name: 'Mot de passe' }).fill('motdepasse123')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    const alert = page.getByRole('alert').filter({ hasText: 'Erreur de connexion' })
    await expect(alert).toBeVisible()
    await expect(alert).toContainText('Email déjà utilisé')
  })

  test('toast erreur — 500 affiche "Erreur serveur"', async ({ page }) => {
    await page.route('**/api/users', (route) =>
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Internal Server Error' }),
      })
    )
    await page.goto('/signup')
    await page.waitForLoadState('networkidle')
    await page.getByRole('textbox', { name: 'Prenom' }).fill('E2E')
    await page.getByRole('textbox', { name: 'Nom', exact: true }).fill('User')
    await page.getByRole('textbox', { name: 'Email' }).fill('e2e@test.com')
    await page.getByRole('textbox', { name: 'Mot de passe' }).fill('motdepasse123')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    const alert = page.getByRole('alert').filter({ hasText: 'Erreur de connexion' })
    await expect(alert).toBeVisible()
    await expect(alert).toContainText('Erreur serveur')
  })
})
