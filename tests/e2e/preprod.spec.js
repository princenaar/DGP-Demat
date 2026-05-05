import { test, expect } from '@playwright/test';

test('public homepage and verification flow are available', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByRole('heading', { name: /Portail de dématérialisation/i })).toBeVisible();
    await page.getByPlaceholder('Ex. ABCD-2345').fill('XXXX-9999');
    await page.getByRole('button', { name: /Vérifier/i }).click();

    await expect(page.getByText(/Code de vérification invalide/i)).toBeVisible();
});

test('admin can sign in and access dashboard settings', async ({ page }) => {
    await page.goto('/login');

    await page.getByLabel('E-mail').fill('princenaar@gmail.com');
    await page.getByLabel('Mot de passe').fill('Passer@789');
    await page.getByRole('button', { name: /Se connecter/i }).click();

    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.getByText(/Tableau de bord/i).first()).toBeVisible();

    await page.goto('/parametres');
    await expect(page.getByText(/Paramètres/i).first()).toBeVisible();
});

test('public user can create a demande with a testing recaptcha token', async ({ page }) => {
    await page.goto('/demandes/create');

    await page.getByRole('button', { name: /Attestation de travail/i }).click();
    await page.getByLabel(/Prénom/i).fill('Awa');
    await page.getByLabel(/^Nom/i).fill('Diop');
    await page.getByLabel('Contractuel').check();
    await page.getByLabel(/Numéro d'Identification National/i).fill('1234567890123');
    await page.locator('#structure_id').selectOption({ index: 1 });
    await page.getByLabel(/Email/i).fill('awa.diop@example.test');
    await page.getByLabel(/Numéro de téléphone/i).fill('+221 77 123 45 67');
    await page.getByLabel(/Date de prise de service/i).fill('2024-01-15');
    await page.getByRole('button', { name: /Soumettre la demande/i }).click();

    await expect(page.getByText(/Votre demande a été enregistrée avec succès/i)).toBeVisible();
});
