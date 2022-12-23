const { test, expect } = require('@playwright/test');

test.describe.parallel('Test dashboard landing page @flow=home', () => {
  test('should show heading when visiting home page @priority=normal', async ({ page }) => {
    await page.goto('/app/dashboard');
    // Expect a title "to contain" a substring.
    await expect(page).toHaveTitle(/Razorpay Dashboard/);

    // const datePicker = page.locator('#analytics-daterange-picker');
    // await expect(datePicker).toBeVisible();
  });
});
