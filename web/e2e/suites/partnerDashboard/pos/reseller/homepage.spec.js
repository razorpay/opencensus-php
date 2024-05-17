import { CONTENT_SELECTORS } from 'partnerDashboard/common/constants';
import { loadPartnerDashboardHomePage } from 'partnerDashboard/common/utils';
import { getStorageStatePath, BASE_PATH } from 'testConstants';
import { waitForSelectorToBeVisible } from 'utils/common';

const { test } = require('utils/base');

// Reseller Partner POS Tests
test.describe.parallel(
  'Test Reseller Partner POS Dashboard landing page @flow=partnerships-pos @project=partner-dashboard',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).RESELLER_PARTNER_POS_TEST_LOGIN_STATE,
    });
    test.beforeEach(async ({ page }) => {
      await loadPartnerDashboardHomePage(
        page,
        'RESELLER_PARTNER_POS',
        CONTENT_SELECTORS.HOME_PAGE.RESELLER_PARTNER_POS_WELCOME_TEXT,
      );
    });

    test('should load the Reseller Partner POS Dashboard @priority=critical', async ({ page }) => {
      // POS Banner
      await page.getByRole('button', { name: 'Add POS Agent' }).click();

      await waitForSelectorToBeVisible({ page, selector: 'text=Invite New Member' });
    });
  },
);
