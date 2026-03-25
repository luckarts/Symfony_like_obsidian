import { test, expect } from '@playwright/test'

test.describe('@smoke Signup', () => {
  test('affichage — 3 champs + bouton submit visible', async ({ page }) => {
    await page.goto('/auth/signup')
    await expect(page.getByLabel('Nom complet')).toBeVisible()
    await expect(page.getByLabel('Email')).toBeVisible()
    await expect(page.getByLabel('Mot de passe')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Créer mon compte' })).toBeVisible()
  })

  test('validation client — nom vide bloque le submit', async ({ page }) => {
    await page.goto('/auth/signup')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByText('Le nom est requis')).toBeVisible()
  })

  test('validation client — email invalide', async ({ page }) => {
    await page.goto('/auth/signup')
    await page.getByLabel('Nom complet').fill('Test User')
    await page.getByLabel('Email').fill('pas-un-email')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByText('Adresse email invalide')).toBeVisible()
  })

  test('validation client — password trop court', async ({ page }) => {
    await page.goto('/auth/signup')
    await page.getByLabel('Nom complet').fill('Test User')
    await page.getByLabel('Email').fill('test@example.com')
    await page.getByLabel('Mot de passe').fill('court')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page.getByText('au moins 8 caractères')).toBeVisible()
  })

  test('happy path — signup réussi redirige vers /', async ({ page }) => {
    const email = `e2e-signup-${Date.now()}@test.com`
    await page.goto('/auth/signup')
    await page.getByLabel('Nom complet').fill('E2E User')
    await page.getByLabel('Email').fill(email)
    await page.getByLabel('Mot de passe').fill('motdepasse123')
    await page.getByRole('button', { name: 'Créer mon compte' }).click()
    await expect(page).toHaveURL('/')
  })
})
