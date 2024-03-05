import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { waitForSelectorToBeVisible } from 'utils/common';

import { CTA_SELECTORS, CONTENT_SELECTORS, WELCOME_TEXT_SELECTORS } from './constants';

const { test, expect } = require('@playwright/test');
const TIMEOUT = 20 * 1000;
// Reseller Partner POS Tests
test.describe.parallel(
  'Test Reseller Partner pos @flow=partnership-pos @project=partner-dashboard',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).RESELLER_PARTNER_POS_TEST_LOGIN_STATE,
    });

    // This skip will be temporary until the partner pos complete flow is ready
    test.skip('should load the Client Accounts lists for Reseller with correct CTAs @priority=critical', async ({
      page,
    }) => {
      // Waits for all js-bundles to load
      await waitForSelectorToBeVisible(
        { page, selector: WELCOME_TEXT_SELECTORS.RESELLER_POS_WELCOME_TEXT },
        { timeout: TIMEOUT },
      );
      await page.goto(routes.AFFILIATE_ACCOUNTS);
      const posProductButton = await page.locator(CTA_SELECTORS.PRODUCT_TABS.POS);
      await posProductButton.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      });

      await page.click('a:has-text("acc_NaHxbfdJ16o2O8")');
      await waitForSelectorToBeVisible(
        { page, selector: 'text=POS Submerchant' },
        { timeout: TIMEOUT },
      );

      await expect(page.locator('text=Account ID')).toBeVisible();
      await expect(page.locator('text=acc_NaHxbfdJ16o2O8')).toBeVisible();
      await expect(page.locator('text=KYC History:')).toBeVisible();
      await expect(page.locator('text=Under Review:')).toBeVisible();
    });
  },
);
