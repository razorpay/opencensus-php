import { routes, StorageStatePath } from '../../utils/constants';

const { test, expect } = require('@playwright/test');
const { resolve } = require('path');

// Reseller Partner capital Tests
test.describe
  .parallel('Test Reseller Partner capital @flow=partnership-capital @project=partner-dashboard', () => {
  test.use({
    storageState: StorageStatePath.CAPITAL_RESELLER_PARTNER_TEST_LOGIN_STATE,
  });

  test('should load the capital Reseller Partner Dashboard @priority=critical', async ({
    page,
  }) => {
    await page.goto(routes.AFFILIATE_ACCOUNTS_CAPITAL);
    await page.waitForSelector('thead th:has-text("Account Name")');

    await page.waitForSelector('div.typeform-sidetab-button-icon[data-testid="close-icon"]');
    const closeButton = await page.$('div.typeform-sidetab-button-icon[data-testid="close-icon"]');
    if (closeButton) {
      await closeButton.click();
    }

    // Create Bureau link
    await page.click('a:has-text("shwt")');
    await page.waitForSelector('button.link_button');
    await expect(page.locator('text=Show More Details')).toBeVisible();
    await page.locator('text=Show More Details').click();
    await page.locator('[data-testid="create-bureau-link-btn"] >> text=Create Bureau Link').click();
    await page.waitForSelector('button[data-blade-component="button"] >> text="Copy Link"');
    await expect(page.locator('text=Line Of Credit Bureau')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Copy Link' })).toBeVisible();

    const closeModalButton = await page.locator('button[aria-label="Close"]');
    await closeModalButton.click();

    // Upload bank document
    await page.click('a:has-text("test user")');
    await page.waitForSelector('button.link_button');
    const uploadStatementButton = await page.getByRole('button', {
      name: 'Upload bank a/c document',
    });
    await uploadStatementButton.click();
    await expect(page.locator('text=Upload bank account statement')).toBeVisible();
    await page.setInputFiles('input[type="file"]', resolve(__dirname, 'test-doc.pdf'));
    await expect(page.locator('text=File Uploaded Successfully!')).toBeVisible();

    const closeUploadModal = await page.locator('button[aria-label="Close"]');
    await closeUploadModal.click();
  });
});
