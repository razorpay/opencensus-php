import { CONTENT_SELECTORS, CTA_SELECTORS, loadPartnerDashboardHomePage } from '../common';

import {
  test,
  getStorageStatePath,
  waitForSelectorToBeVisible,
} from '@libs/shared-qsuite/playwright';

// Aggregator Partner Tests
test.describe
  .parallel('Test Aggregator Partner Dashboard landing page @flow=partner-homepage @project=partner-dashboard', () => {
  test.use({
    storageState: getStorageStatePath().AGGREGATOR_PARTNER_TEST_LOGIN_STATE,
  });
  test.beforeEach(async ({ page }) => {
    await loadPartnerDashboardHomePage(
      page,
      'AGGREGATOR_PARTNER',
      CONTENT_SELECTORS.HOME_PAGE.AGGREGATOR_WELCOME_TEXT,
    );
  });
  test.skip('should load the Aggregator Partner Dashboard @priority=critical', async ({ page }) => {
    await page.locator(CTA_SELECTORS.HOME_PAGE.REFER_NEW_CLIENT).click();
    await waitForSelectorToBeVisible({
      page,
      selector: CONTENT_SELECTORS.SELECT_PRODUCT.LEGACY_HEADER,
    });
    // TODO v2: add more checks separately
  });
});
