const { test, expect } = require('@playwright/test');
const { StorageStatePath, routes } = require('../../utils/constants');

test.describe
  .parallel('Test International Method banner on homepage @flow=ie @project=payments @project=payments-roast', () => {
  test.use({
    storageState: StorageStatePath.ACTIVATED_NOT_IE_STATE,
  });
  // roast test verifyViewInternationalMethodsTest
  test.skip('should be IE banner and link should redirect to IE page @priority=normal @suite=payments-automation', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);

    await page.waitForTimeout(5000);

    const ieCTA = await page.getByRole('link', { name: 'View International Methods' });
    await expect(ieCTA).toBeVisible();
    await ieCTA.click();

    // wait for redirection to complete based on whether IE Revamp is enabled or not
    await page.waitForTimeout(2000);

    const url = await page.url();
    const { pathname, search, hash } = new URL(url);
    const fullPath = pathname + search + hash;

    // depending on IE Revamp Experiment status it could be either
    expect(fullPath).toMatch(
      /^\/app\/payment-methods(\/international-payments|\?instrument=international)?$/,
    );
  });
});
