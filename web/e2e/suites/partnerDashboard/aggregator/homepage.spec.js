import { CONTENT_SELECTORS, CTA_SELECTORS } from 'partnerDashboard/common/constants';
import { loadPartnerDashboardHomePage } from 'partnerDashboard/common/utils';
import { getStorageStatePath, BASE_PATH } from 'testConstants';
import { waitForSelectorToBeVisible } from 'utils/common';

const { test } = require('utils/base');

// Aggregator Partner Tests
test.describe
  .parallel('Test Aggregator Partner Dashboard landing page @flow=partner-homepage @project=partner-dashboard', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).AGGREGATOR_PARTNER_TEST_LOGIN_STATE,
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
