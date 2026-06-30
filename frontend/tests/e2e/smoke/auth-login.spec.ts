import { expect, test } from '@playwright/test'

test.describe('@smoke Login', () => {
  test('affichage — 2 champs + bouton submit + liens visibles', async ({ page }) => {
    await page.goto('/auth/login')
    await page.waitForLoadState('networkidle')
    await expect(page.getByLabel('Email')).toBeVisible()
    await expect(page.getByLabel('Mot de passe')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Se connecter' })).toBeVisible()
    await expect(page.getByText('Pas encore de compte ?')).toBeVisible()
  })

  test('validation client — email vide bloque submit', async ({ page }) => {
    await page.goto('/auth/login')
    await page.waitForLoadState('networkidle')
    await page.getByRole('button', { name: 'Se connecter' }).click()
    await expect(page.getByText('Ce champ est requis')).toBeVisible()
  })

  test('validation client — email invalide', async ({ page }) => {
    await page.goto('/auth/login')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Email').fill('pas-un-email')
    await page.getByRole('button', { name: 'Se connecter' }).click()
    await expect(page.getByText('Adresse email invalide')).toBeVisible()
  })

  test('validation client — password vide', async ({ page }) => {
    await page.goto('/auth/login')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Email').fill('test@example.com')
    await page.getByRole('button', { name: 'Se connecter' }).click()
    await expect(page.getByText('Ce champ est requis')).toBeVisible()
  })

  test('lien vers signup — navigate vers /auth/signup', async ({ page }) => {
    await page.goto('/auth/login')
    await page.waitForLoadState('networkidle')
    await page.getByRole('link', { name: "S'inscrire" }).click()
    await expect(page).toHaveURL('/auth/signup')
  })

  test('toast succès — redirige vers /', async ({ page }) => {
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
    await page.goto('/auth/login')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Email').fill('user@test.com')
    await page.getByLabel('Mot de passe').fill('password123')
    await page.getByRole('button', { name: 'Se connecter' }).click()
    await expect(
      page.getByRole('alert').filter({ hasText: 'Content de vous revoir' })
    ).toBeVisible()
    await expect(page).toHaveURL('/')
  })

  test('toast erreur — 401 affiche "Identifiants invalides"', async ({ page }) => {
    await page.route('**/oauth2/token', (route) =>
      route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Invalid credentials' }),
      })
    )
    await page.goto('/auth/login')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Email').fill('wrong@test.com')
    await page.getByLabel('Mot de passe').fill('wrongpass')
    await page.getByRole('button', { name: 'Se connecter' }).click()
    const alert = page.getByRole('alert').filter({ hasText: 'Erreur de connexion' })
    await expect(alert).toBeVisible()
    await expect(alert).toContainText('Identifiants invalides')
  })

  test('toast erreur — 500 affiche "Erreur serveur"', async ({ page }) => {
    await page.route('**/oauth2/token', (route) =>
      route.fulfill({
        status: 500,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Internal Server Error' }),
      })
    )
    await page.goto('/auth/login')
    await page.waitForLoadState('networkidle')
    await page.getByLabel('Email').fill('user@test.com')
    await page.getByLabel('Mot de passe').fill('password123')
    await page.getByRole('button', { name: 'Se connecter' }).click()
    const alert = page.getByRole('alert').filter({ hasText: 'Erreur de connexion' })
    await expect(alert).toBeVisible()
    await expect(alert).toContainText('Erreur serveur')
  })
})
