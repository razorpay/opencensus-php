import { routes, StorageStatePath } from '../../utils/constants';

const { test, expect } = require('@playwright/test');

const SELECTORS = {
  METHOD_PAYOUT_FUND_TRANSFER: 'text=Method: Payout: Fund Transfer',
  AMOUNT_RANGE_TEXT: 'text=1000-25000',
};

test.describe.parallel('Test Partner Pricing Plans page @project=partner-dashboard', () => {
  test.use({
    storageState: StorageStatePath.RESELLER_PARTNER_TEST_LOGIN_STATE,
  });

  test('should load the pricing page accurately @priority=critical', async ({ page }) => {
    await page.goto(`${routes.PARTNER_PRICING_PLANS}?partner_id=McZVEtDSEQ6s9H`);
    await page.waitForSelector(SELECTORS.METHOD_PAYOUT_FUND_TRANSFER);
    let conditionalElement = await page.locator(SELECTORS.AMOUNT_RANGE_TEXT);
    await expect(conditionalElement).not.toBeVisible();
    const walletTableExpand = await page.locator(SELECTORS.METHOD_PAYOUT_FUND_TRANSFER);
    await walletTableExpand.click();
    conditionalElement = await page.locator(SELECTORS.AMOUNT_RANGE_TEXT);
    await expect(conditionalElement).toBeVisible();
  });
});
