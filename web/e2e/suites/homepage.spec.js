const { test, expect } = require('@playwright/test');
const { StorageStatePath } = require('../utils/constants');

test.describe.parallel('Test dashboard landing page @flow=home', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });
  test('should show heading when visiting home page @priority=normal', async ({ page }) => {
    await page.goto('/app/dashboard');
    // Expect a title "to contain" a substring.
    await expect(page).toHaveTitle(/Razorpay Dashboard/);

    // const datePicker = page.locator('#analytics-daterange-picker');
    // await expect(datePicker).toBeVisible();
  });
});
