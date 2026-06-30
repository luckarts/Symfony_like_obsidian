import { expect, test } from "@playwright/test";

test.describe("@smoke Auth Flow (real stack)", () => {
  test("signup then login — full real API flow", async ({ page }) => {
    test.skip(!!process.env.CI, "Backend Symfony non disponible en CI");

    const email = `e2e-flow-${Date.now()}@test.com`;
    const password = "E2eStr0ng!Pass";

    // ─── Step 1: Signup ──────────────────────────────────────
    await page.goto("/auth/signup");
    await page.waitForLoadState("networkidle");

    await page.getByLabel("Prénom").fill("E2E");
    await page.getByLabel("Nom", { exact: true }).fill("User");
    await page.getByLabel("Email").fill(email);
    await page.getByLabel("Mot de passe").fill(password);
    await page.getByRole("button", { name: "Créer mon compte" }).click();

    // Wait for successful signup → toast + redirect /
    await expect(
      page.getByRole("alert").filter({ hasText: "Bienvenue" }),
    ).toBeVisible({ timeout: 15000 });
    await expect(page).toHaveURL("/");

    // ─── Step 2: Login ───────────────────────────────────────
    await page.goto("/auth/login");
    await page.waitForLoadState("networkidle");

    await page.getByLabel("Email").fill(email);
    await page.getByLabel("Mot de passe").fill(password);
    await page.getByRole("button", { name: "Se connecter" }).click();

    // Wait for successful login → toast + redirect /
    await expect(
      page.getByRole("alert").filter({ hasText: "Content de vous revoir" }),
    ).toBeVisible({ timeout: 15000 });
    await expect(page).toHaveURL("/");
  });
});
