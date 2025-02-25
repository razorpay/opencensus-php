import { CONTENT_SELECTORS, loadPartnerDashboardHomePage } from '../../common';
import {
  test,
  waitForSelectorToBeVisible,
  getStorageStatePath,
} from '@libs/shared-qsuite/playwright';

// Reseller Partner POS Tests
test.describe
  .parallel('Test Reseller Partner POS Dashboard landing page @flow=partnerships-pos @project=partner-dashboard', () => {
  test.use({
    storageState: getStorageStatePath().RESELLER_PARTNER_POS_TEST_LOGIN_STATE,
  });
  test.beforeEach(async ({ page }) => {
    await loadPartnerDashboardHomePage(
      page,
      'RESELLER_PARTNER_POS',
      CONTENT_SELECTORS.HOME_PAGE.RESELLER_PARTNER_POS_WELCOME_TEXT,
    );
  });

  test.skip('should load the Reseller Partner POS Dashboard @priority=critical', async ({
    page,
  }) => {
    // POS Banner
    await page.getByRole('button', { name: 'Add POS Agent' }).click();

    await waitForSelectorToBeVisible({ page, selector: 'text=Invite New Member' });
  });
});
