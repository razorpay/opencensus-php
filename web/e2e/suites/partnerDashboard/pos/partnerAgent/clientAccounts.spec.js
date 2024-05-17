import {
  CONTENT_SELECTORS,
  CTA_SELECTORS,
  INPUT_SELECTORS,
} from 'partnerDashboard/common/constants';
import {
  clickAndLoadAcceptedInvites,
  clickAndLoadAllInvites,
  clickSubmerchantDetailsAndValidate,
  fillInputAndLoadSearchResults,
  loadClientAccountsDirectly,
  loadPOSViewOrderDetailsTab,
} from 'partnerDashboard/common/utils';
import { getStorageStatePath, BASE_PATH, routes } from 'testConstants';
import { waitForSelectorToBeVisible } from 'utils/common';

const { test, expect } = require('utils/base');

// Reseller Partner POS Agent Tests
test.describe.parallel(
  'Test Reseller Partner POS Agent pos @flow=partnerships-pos @project=partner-dashboard',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).RESELLER_PARTNER_AGENT_TEST_LOGIN_STATE,
    });

    test.beforeEach(async ({ page }) => {
      await loadClientAccountsDirectly(
        page,
        'RESELLER_PARTNER_POS',
        CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      );
      // Expect to be redirected to /pos
      await expect(page).toHaveURL(new RegExp(`${routes.CLIENT_ACCOUNTS_POS}/?`));
    });

    test('should load the Accepted Invite list and Details Panel with correct CTAs @priority=critical', async ({
      page,
    }) => {
      const merchantId = 'NaiZ60619zINVd';
      const accountId = `acc_${merchantId}`;
      const accountName = 'POS Submerchant One';
      // Load POS accepted invites
      await clickAndLoadAcceptedInvites(page);
      // Search for pos submerchant
      await fillInputAndLoadSearchResults(
        page,
        INPUT_SELECTORS.CLIENTS_LIST.ACCOUNT_ID_FILTER,
        merchantId,
        CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      );

      // Click on submerchant detail link
      await clickSubmerchantDetailsAndValidate(
        page,
        routes.CLIENT_ACCOUNTS_POS,
        accountId,
        accountName,
      );

      // TODO: expect inviter agent name to be visible once available from BE
      await expect(page.locator(`.ModalSlider__Content :text-is("${accountName}")`)).toBeVisible();

      // Additional POS submerchant detail checks
      await expect(page.locator('.ModalSlider__Content :text-is("KYC History:")')).toBeVisible();
      // Check current activation status
      await expect(page.locator('.ModalSlider__Content :text-is("Under Review")')).toHaveCount(2);

      // Check View Order details CTA
      const newTab = await loadPOSViewOrderDetailsTab(page, accountId);
      await expect(newTab.locator(CONTENT_SELECTORS.CLIENT_ORDERS.HEADER)).toBeVisible();

      await expect(newTab.locator(`text=${accountId}`)).toBeVisible();
      await expect(newTab.locator(`text=${accountName}`)).toBeVisible();
      // TODO: expect client orders list to be visible when data available from BE
    });

    test.skip('should load the All Invites list for Reseller Partner POS with correct CTAs @priority=critical', async ({
      page,
    }) => {
      const inviterEmail = 'invited.by@agent.com';
      const accountName = 'POS Submerchant One';
      // Check POS All Invites List
      await clickAndLoadAllInvites(page);

      // Search for pos submerchant invited by this account
      await fillInputAndLoadSearchResults(
        page,
        INPUT_SELECTORS.CLIENTS_LIST.EMAIL_ID_FILTER,
        inviterEmail,
        CONTENT_SELECTORS.CLIENTS_LIST.ALL_INVITES.LAST_INVITED_ON,
      );

      // TODO: expect inviter agent name to be visible once available from BE
      await expect(page.locator(`.ModalSlider__Content :text-is("${accountName}")`)).toBeVisible();

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
