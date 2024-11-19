const { BASE_PATH, getStorageStatePath, routes } = require('testConstants');
const { test, expect } = require('utils/base');

const ELEMENT_CONFIG = {
  LANDING_PATH_REGEXP:
    /^\/app\/payment-methods(\/international-payments|\?instrument=international)?$/,
  CTA_NAME: 'View International Methods',
  KYC_MODAL: 'h1 >> text="KYC Submitted"',
  CLOSE_MODAL: 'span[class="Modal-close  "] >> text="×"',
};

test.describe.parallel(
  'Test International Method banner on homepage @flow=ie @project=payments @project=payments-roast',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_NOT_IE_STATE,
    });

    // roast test verifyViewInternationalMethodsTest
    test('should be IE banner and link should redirect to IE page @priority=normal @suite=payments-automation', async ({
      page,
    }) => {
      await page.goto(routes.DASHBOARD);

      await page.waitForTimeout(5000);

      const ieCTA = page.getByRole('link', { name: ELEMENT_CONFIG.CTA_NAME });
      await expect(ieCTA).toBeVisible();

      // redirect to international payments
      await ieCTA.click();

      // wait for redirection to complete based on whether IE Revamp is enabled or not
      await page.waitForTimeout(2000);

      const url = await page.url();
      const { pathname, search, hash } = new URL(url);
      const fullPath = pathname + search + hash;

      // depending on IE Revamp Experiment status it could be either
      expect(fullPath).toMatch(ELEMENT_CONFIG.LANDING_PATH_REGEXP);
    });
  },
);