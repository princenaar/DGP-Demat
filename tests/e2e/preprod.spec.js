import { test, expect } from '@playwright/test';

async function signInAsAdmin(page) {
    await page.goto('/login');

    await page.getByLabel('E-mail').fill('princenaar@gmail.com');
    await page.getByLabel('Mot de passe').fill('Passer@789');
    await page.getByRole('button', { name: /Se connecter/i }).click();

    await expect(page).toHaveURL(/\/dashboard$/);
}

test('public homepage and verification flow are available', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByRole('heading', { name: /Portail de dématérialisation/i })).toBeVisible();
    await page.getByPlaceholder('Ex. ABCD-2345').fill('XXXX-9999');
    await page.getByRole('button', { name: /Vérifier/i }).click();

    await expect(page.getByText(/Code de vérification invalide/i)).toBeVisible();
});

test('admin can sign in and access dashboard settings', async ({ page }) => {
    await signInAsAdmin(page);
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
    await page.locator('#categorie_socioprofessionnelle_id').selectOption({ index: 1 });
    await page.getByLabel(/Date de prise de service/i).fill('2024-01-15');
    await page.getByRole('button', { name: /Soumettre la demande/i }).click();

    await expect(page.getByText(/Votre demande a été enregistrée avec succès/i)).toBeVisible();
});

test('ADM configured as etatique keeps public status radios coherent', async ({ page }) => {
    await signInAsAdmin(page);

    await page.goto('/parametres/types-demandes');
    const admRow = page.locator('tbody tr', { has: page.getByText('ADM', { exact: true }) });
    await admRow.getByRole('link', { name: 'Modifier' }).click();
    await page.getByLabel(/Statut éligible/i).selectOption('étatique');
    for (const field of [
        'categorie_socioprofessionnelle_id',
        'date_prise_service',
        'date_fin_service',
        'date_depart_retraite',
        'date_naissance',
        'lieu_naissance',
    ]) {
        await page.locator(`input[name="champs_requis[${field}]"]`).uncheck();
    }
    await page.getByRole('button', { name: /Enregistrer/i }).click();

    await expect(page.getByText(/Type de demande mis à jour/i)).toBeVisible();

    await page.goto('/demandes/create');
    await page.getByRole('button', { name: /Certificat administratif/i }).click();

    const etatiqueRadio = page.locator('input[type="radio"][value="étatique"]');
    const contractuelRadio = page.locator('input[type="radio"][value="contractuel"]');

    await expect(page.locator('input[type="hidden"][name="statut"]')).toHaveValue('étatique');
    await expect(etatiqueRadio).toBeChecked();
    await expect(etatiqueRadio).toBeDisabled();
    await expect(contractuelRadio).toBeDisabled();
    await expect(page.locator('#matricule')).toBeVisible();
    await expect(page.locator('#categorie_socioprofessionnelle_id')).toBeHidden();
    await expect(page.locator('#date_naissance')).toBeHidden();
    await expect(page.locator('#lieu_naissance')).toBeHidden();
    await expect(page.locator('#date_prise_service')).toBeHidden();
    await expect(page.locator('#date_fin_service')).toBeHidden();
    await expect(page.locator('#date_depart_retraite')).toBeHidden();

    await page.getByLabel(/Prénom/i).fill('Mamadou');
    await page.getByLabel(/^Nom/i).fill('Sarr');
    await page.getByLabel(/Matricule/i).fill('123456A');
    await page.getByLabel(/Numéro d'Identification National/i).fill('2234567890123');
    await page.locator('#structure_id').selectOption({ index: 1 });
    await page.getByLabel(/Email/i).fill('mamadou.sarr@example.test');
    await page.getByLabel(/Numéro de téléphone/i).fill('+221 78 222 22 22');
    await page.getByRole('button', { name: /Soumettre la demande/i }).click();

    await expect(page.getByText(/Votre demande a été enregistrée avec succès/i)).toBeVisible();
});
