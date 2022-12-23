const { test, expect } = require('@playwright/test');
const { credentials } = require('../utils/constants');

test.describe.parallel('Dashboard login flow @flow=login', () => {
  test.use({
    storageState: undefined,
  });

  test('should login with credentials @priority=critical', async ({ page }) => {
    await page.goto('/?screen=sign_in');

    // Expect a title "to contain" a substring.
    await expect(page).toHaveTitle(/Razorpay Dashboard/);

    await page.click('input[type="text"]');
    await page.fill('input[type="text"]', credentials.username);
    await page.click('text="Next"');
    await page.click('input[type="password"]');
    await page.fill('input[type="password"]', credentials.password);

    await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);

    await expect(page).toHaveURL('/app/dashboard');
  });
});
