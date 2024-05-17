import { BASE_PATH, getStorageStatePath, routes } from 'testConstants';

const { test, expect } = require('utils/base');

const BusinessProvier = {
  AiSensy: {
    title: 'AISensy',
    providerPageTitle: 'AiSensy',
    existingAccountUrl: 'aisensy.com',
    newAccountUrl: 'aisensy.com',
  },
};

const mockApiResponseForConnectedApplication = async ({ page }) => {
  await page.route('**/oauth/submerchant/applications', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status_code: 200,
        success: true,
        data: {
          entity: 'collection',
          count: 1,
          items: [
            {
              application_name: 'AiSensy',
              application_id: 'aisensy',
              access_granted_at: 1689760125,
              scopes: [
                {
                  scope: 'read_write',
                  description: 'Ai sensy',
                },
              ],
              logo_url: '/logos/MFc62o972tlkRE.jpeg',
            },
          ],
        },
      }),
    });
  });
};

test.describe('Whatsapp Setup Settings @flow=whatsapp-setup @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).EMAIL_TEST_LOGIN_STATE,
  });
  test('should show Whatsapp account setup page @priority=normal', async ({ page }) => {
    await page.goto(routes.WHATSAPP_ACCOUNT_SETUP);

    await expect(page.getByText('Whatsapp Account Set-up', { exact: true })).toBeVisible();
    await expect(
      page.getByText('Continue by linking your existing WABA account', { exact: true }),
    ).toBeVisible();

    await expect(page.getByRole('button', { name: 'Proceed' })).toBeVisible();
    await expect(page.getByText('Don’t have an account?')).toBeVisible();
    await expect(
      page.getByRole('link', { name: 'Create Whatsapp Business Account' }),
    ).toBeVisible();
  });

  test('existing account link flow should work @priority=normal', async ({ page }) => {
    await page.goto(routes.WHATSAPP_ACCOUNT_SETUP);
    const { title, providerPageTitle, existingAccountUrl } = BusinessProvier.AiSensy;

    const linkExistingAccount = page.getByRole('button', {
      name: 'Proceed',
    });
    await expect(linkExistingAccount).toBeVisible();
    await linkExistingAccount.click();

    await page.getByText('Select your Business Service Provider').click();
    await page.getByRole('option', { name: title }).click();

    const context = page.context();
    const pagePromise = context.waitForEvent('page');

    await page.getByRole('button', { name: 'Continue' }).click();
    const newPage = await pagePromise;
    await newPage.waitForLoadState();
    const newPageTitle = await newPage.title();
    const newPageUrl = await newPage.url();
    await expect(newPageTitle).toBe(providerPageTitle);
    await expect(newPageUrl).toContain(existingAccountUrl);

    await expect(page.getByText('We’ve initiated the integration', { exact: true })).toBeVisible();
    await expect(page.getByText('Integration is in progress')).toBeVisible();
  });

  test('create new account flow should work @priority=normal', async ({ page }) => {
    await page.goto(routes.WHATSAPP_ACCOUNT_SETUP);
    const { providerPageTitle, newAccountUrl } = BusinessProvier.AiSensy;

    const createNewAccountBtn = page.getByRole('link', {
      name: 'Create Whatsapp Business Account',
    });
    await expect(createNewAccountBtn).toBeVisible();
    const context = page.context();
    const pagePromise = context.waitForEvent('page');
    await createNewAccountBtn.click();
    const newPage = await pagePromise;
    await newPage.waitForLoadState();
    const newPageTitle = await newPage.title();
    const newPageUrl = await newPage.url();
    await expect(newPageTitle).toBe(providerPageTitle);
    await expect(newPageUrl).toContain(newAccountUrl);
  });

  test('should show success modal on callback url @priority=normal', async ({ page }) => {
    await mockApiResponseForConnectedApplication({ page });
    await page.goto(`${routes.WHATSAPP_ACCOUNT_SETUP}/?isWhatsappSetupCompleted=true`);

    await expect(page.getByText('Set-up completed successfully!', { exact: true })).toBeVisible();
    await expect(
      page.getByText(
        'We’ve verified your details. Notifcations are now enabled for your payment links',
      ),
    ).toBeVisible();
  });

  test('should show already connected account flow @priority=normal', async ({ page }) => {
    await mockApiResponseForConnectedApplication({ page });
    await page.goto(routes.WHATSAPP_ACCOUNT_SETUP);

    await expect(page.getByText('Your Whatsapp Business Account', { exact: true })).toBeVisible();
    await expect(
      page.getByText('Send all payment links on Whatsapp', { exact: true }),
    ).toBeVisible();
    await expect(page.getByRole('button', { name: 'Delete' })).toBeVisible();
    await expect(page.locator('label')).toBeVisible();
  });

  test('should show delete CTA when app is already connected @priority=normal', async ({
    page,
  }) => {
    await mockApiResponseForConnectedApplication({ page });
    await page.goto(routes.WHATSAPP_ACCOUNT_SETUP);

    const deleteBtn = await page.getByRole('button', { name: 'Delete' });
    await expect(deleteBtn).toBeVisible();
    await deleteBtn.click();

    await expect(page.getByRole('button', { name: 'Yes, Delete' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Cancel' })).toBeVisible();
  });

  test('should show notification toggle switch when app is already connected @priority=normal', async ({
    page,
  }) => {
    await mockApiResponseForConnectedApplication({ page });
    await page.goto(routes.WHATSAPP_ACCOUNT_SETUP);

    const toggleSwitch = await page.locator('label');
    await expect(toggleSwitch).toBeVisible();
    await toggleSwitch.click();

    await expect(page.getByRole('button', { name: 'Yes, I understand' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Cancel' })).toBeVisible();
  });
});
