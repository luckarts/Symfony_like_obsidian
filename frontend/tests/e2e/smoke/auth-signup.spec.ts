import { test, expect } from '@playwright/test'

test.describe('@smoke Signup', () => {
  test('affichage — 4 champs + bouton submit visible', async ({ page }) => {
    await page.goto('/auth/signup')
    await page.waitForLoadState('networkidle')
    await expect(page.getByLabel('Prénom')).toBeVisible()
    await expect(page.getByLabel('Nom', { exact: true })).toBeVisible()
    await expect(page.getByLabel('Email')).toBeVisible()
    await expect(page.getByLabel('Mot de passe')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Créer mon compte' })).toBeVisible()
  })

  test('validation client — prénom vide bloque le submit', async ({ page }) => {
    await page.goto('/auth/signup')
    await page.waitForLoadState('networkidle')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByText('Le prénom est requis').first()).toBeVisible()
  })

  test('validation client — email invalide', async ({ page }) => {
    await page.goto('/auth/signup')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Prénom').fill('E2E')
    await page.getByLabel('Nom', { exact: true }).fill('User')
    await page.getByLabel('Email').fill('pas-un-email')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByText('Adresse email invalide')).toBeVisible()
  })

  test('validation client — password trop court', async ({ page }) => {
    await page.goto('/auth/signup')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Prénom').fill('E2E')
    await page.getByLabel('Nom', { exact: true }).fill('User')
    await page.getByLabel('Email').fill('test@example.com')
    await page.getByLabel('Mot de passe').fill('court')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByText('au moins 8 caractères')).toBeVisible()
  })

  test('toast succès — "Bienvenue !" affiché après signup réussi', async ({ page }) => {
    // signup → login (full flow)
    await page.route('**/api/register', (route) =>
      route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({ token: 'fake-token', user: { id: 1, name: 'E2E User', email: 'e2e@test.com' } }),
      }),
    )
    await page.route('**/oauth2/token', (route) =>
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          access_token: 'fake-jwt-token',
          refresh_token: 'fake-refresh',
          token_type: 'bearer',
          expires_in: 3600,
        }),
      })
    )
    await page.goto('/auth/signup')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Prénom').fill('E2E')
    await page.getByLabel('Nom', { exact: true }).fill('User')
    await page.getByLabel('Email').fill('e2e@test.com')
    await page.getByLabel('Mot de passe').fill('motdepasse123')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByRole('alert').filter({ hasText: 'Bienvenue !' })).toBeVisible()
  })

  test('toast erreur — 422 affiche "Email déjà utilisé ou données invalides"', async ({ page }) => {
    await page.route('**/api/register', (route) =>
      route.fulfill({
        status: 422,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Unprocessable Entity' }),
      }),
    )
    await page.goto('/auth/signup')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Prénom').fill('E2E')
    await page.getByLabel('Nom', { exact: true }).fill('User')
    await page.getByLabel('Email').fill('taken@test.com')
    await page.getByLabel('Mot de passe').fill('motdepasse123')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    const alert = page.getByRole('alert').filter({ hasText: 'Erreur de connexion' })
    await expect(alert).toBeVisible()
    await expect(alert).toContainText('Email déjà utilisé ou données invalides')
  })

  test('toast erreur — 500 affiche "Erreur serveur"', async ({ page }) => {
    await page.route('**/api/register', (route) =>
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Internal Server Error' }),
      }),
    )
    await page.goto('/auth/signup')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Prénom').fill('E2E')
    await page.getByLabel('Nom', { exact: true }).fill('User')
    await page.getByLabel('Email').fill('e2e@test.com')
    await page.getByLabel('Mot de passe').fill('motdepasse123')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    const alert = page.getByRole('alert').filter({ hasText: 'Erreur de connexion' })
    await expect(alert).toBeVisible()
    await expect(alert).toContainText('Erreur serveur')
  })
})
