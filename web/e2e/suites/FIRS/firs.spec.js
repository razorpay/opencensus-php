const { BASE_PATH, getStorageStatePath, routes } = require('testConstants');
const { test, expect } = require('utils/base');

const ELEMENT_CONFIG = {
  FIRS_TEXT: 'button[role="button"]:has-text("Foreign Inward Remittance Statement")',
};

test.describe.parallel(
  'Test FIRS route when merchant is International @flow=firs @suite=payments-automation @suite=payments-canary @project=payments @project=payments-roast',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    });

    test('should show FIRS page when merchant is international @priority=normal', async ({
      page,
    }) => {
      await page.goto(routes.ACCOUNT_SETTINGS);

      // FIRS CTA click
      await page.locator(ELEMENT_CONFIG.FIRS_TEXT).click();

      //button click should redirect to correct page
      await expect(page).toHaveURL(routes.FIRS);
    });
  },
);
