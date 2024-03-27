import {
  CTA_SELECTORS,
  CONTENT_SELECTORS,
  INPUT_SELECTORS,
} from 'partnerDashboard/common/constants';
import { navigateToClientAccounts } from 'partnerDashboard/common/utils';
import {
  fillInputAndLoadSearchResults,
  clickSubmerchantDetailsAndValidate,
  clickAndLoadAllInvites,
  clickAndLoadAcceptedInvites,
} from 'partnerDashboard/common/utils/clientAccounts';
import { getStorageStatePath, BASE_PATH } from 'testConstants';
import { waitForSelectorToBeVisible } from 'utils/common';

const { test, expect } = require('@playwright/test');

// Reseller Partner POS Tests
test.describe.parallel(
  'Test Reseller Partner POS @flow=partnerships-pos @project=partner-dashboard',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).RESELLER_PARTNER_POS_TEST_LOGIN_STATE,
    });

    test.beforeEach(async ({ page }) => {
      await navigateToClientAccounts(
        page,
        'RESELLER_PARTNER_POS',
        CONTENT_SELECTORS.HOME_PAGE.RESELLER_PARTNER_POS_WELCOME_TEXT,
        CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      );

      // Load POS accepted invites
      await clickAndLoadAcceptedInvites(page, CTA_SELECTORS.PRODUCT_TABS.POS);
    });

    test('should load the Accepted Invite list for Reseller Partner POS with correct CTAs @priority=critical', async ({
      page,
    }) => {
      // Load POS accepted invites
      await clickAndLoadAcceptedInvites(page);
      // Search for pos submerchant
      await fillInputAndLoadSearchResults(
        page,
        INPUT_SELECTORS.CLIENTS_LIST.ACCOUNT_ID_FILTER,
        'NaiZ60619zINVd',
        CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      );

      // Click on submerchant detail link
      await clickSubmerchantDetailsAndValidate(page, 'acc_NaiZ60619zINVd', 'POS Submerchant One');
      // Additional POS submerchant detail checks
      await expect(page.locator('.ModalSlider__Content :text-is("KYC History:")')).toBeVisible();
      // Check current activation status
      await expect(page.locator('.ModalSlider__Content :text-is("Under Review")')).toHaveCount(2);
    });

    // Skipping currently due to edge policy auth issue: https://razorpay.slack.com/archives/C01N2CDSB7H/p1709068980198339?thread_ts=1707804522.326369&cid=C01N2CDSB7H
    test.skip('should load the All Invites list for Reseller Partner POS with correct CTAs @priority=critical', async ({
      page,
    }) => {
      // Check POS All Invites List
      await clickAndLoadAllInvites(page);

      // Search for pos submerchant invited by this account
      await fillInputAndLoadSearchResults(
        page,
        INPUT_SELECTORS.CLIENTS_LIST.EMAIL_ID_FILTER,
        'invited.by@agent.com',
        CONTENT_SELECTORS.CLIENTS_LIST.ALL_INVITES.LAST_INVITED_ON,
      );

      // TODO: expect inviter agent name to be visible once available from BE
      // await expect(page.locator("text=POS Agent One"))

      // Expect a single row response
      await page.locator(CTA_SELECTORS.CLIENTS_LIST.RESEND_INVITE).click();

      // Expect the invite is successful
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.ALL_INVITES.INVITE_SUCCESSFUL,
      });
    });
  },
);
