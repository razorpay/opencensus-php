import { CTA_SELECTORS, CONTENT_SELECTORS, loadPartnerDashboardHomePage } from '../common';
import {
  test,
  getStorageStatePath,
  waitForSelectorToBeVisible,
} from '@libs/shared-qsuite/playwright';

// Reseller Partner Tests
test.describe
  .parallel('Test Reseller Partner Dashboard landing page @flow=partner-homepage @project=partner-dashboard', () => {
  test.use({
    storageState: getStorageStatePath().RESELLER_PARTNER_TEST_LOGIN_STATE,
  });
  test.beforeEach(async ({ page }) => {
    await loadPartnerDashboardHomePage(
      page,
      'RESELLER_PARTNER',
      CONTENT_SELECTORS.HOME_PAGE.RESELLER_PARTNER_WELCOME_TEXT,
    );
  });
  test.skip('should load the Reseller Partner Dashboard @priority=critical', async ({ page }) => {
    await page.locator(CTA_SELECTORS.HOME_PAGE.REFER_NEW_CLIENT).click();
    await waitForSelectorToBeVisible({
      page,
      selector: CONTENT_SELECTORS.SELECT_PRODUCT.LEGACY_HEADER,
    });
    // TODO v2: add more checks separately
  });
});
