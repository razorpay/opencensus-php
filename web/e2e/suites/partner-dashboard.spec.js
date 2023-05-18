const { test, expect } = require('@playwright/test');
const { StorageStatePath } = require('../utils/constants');

test.describe.parallel('Test partner dashboard landing page @flow=home', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });
  test('should load the partner dashboard @priority=critical', async ({ page }) => {
    await page.goto('/app/dashboard');

    await page.locator('text="Partner"').click();

    const partnerDashboardWelcomeText = page.locator(
      'text=Welcome to Reseller Partner dashboard, PlayWright Test Account!',
    );
    await expect(partnerDashboardWelcomeText).toBeVisible();
  });
});
