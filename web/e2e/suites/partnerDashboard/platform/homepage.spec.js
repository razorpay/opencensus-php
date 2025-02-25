import { CONTENT_SELECTORS, CTA_SELECTORS, loadPartnerDashboardHomePage } from '../common';
import {
  test,
  expect,
  getStorageStatePath,
  waitForSelectorToBeVisible,
} from '@libs/shared-qsuite/playwright';

// Platform Partner Tests
test.describe
  .parallel('Test Platform Partner Dashboard landing page @flow=partner-homepage @project=partner-dashboard', () => {
  test.use({
    storageState: getStorageStatePath().PLATFORM_PARTNER_TEST_LOGIN_STATE,
  });
  test.beforeEach(async ({ page }) => {
    await loadPartnerDashboardHomePage(
      page,
      'PLATFORM_PARTNER',
      CONTENT_SELECTORS.HOME_PAGE.PLATFORM_PARTNER_WELCOME_TEXT,
    );
  });
  test.skip('should load the Platform Partner Dashboard @priority=critical', async ({ page }) => {
    await waitForSelectorToBeVisible({
      page,
      selector: CONTENT_SELECTORS.HOME_PAGE.START_YOUR_JOURNEY,
    });
    expect(page.locator(CTA_SELECTORS.HOME_PAGE.REFER_NEW_CLIENT)).not.toBeVisible();
    // TODO v2: add more checks separately
  });
});
