import { CTA_SELECTORS, CONTENT_SELECTORS } from 'partnerDashboard/common/constants';
import { navigateToClientAccounts } from 'partnerDashboard/common/utils';
import { getStorageStatePath, BASE_PATH } from 'testConstants';
import { waitForSelectorToBeVisible } from 'utils/common';

const { test, expect } = require('@playwright/test');

// Reseller Partner Tests
test.describe.parallel(
  'Test Client Accounts for Reseller @flow=partner-homepage @project=partner-dashboard',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).RESELLER_PARTNER_TEST_LOGIN_STATE,
    });
    test.beforeEach(async ({ page }) => {
      await navigateToClientAccounts(
        page,
        'RESELLER_PARTNER',
        CONTENT_SELECTORS.HOME_PAGE.RESELLER_PARTNER_WELCOME_TEXT,
        CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      );
    });
    test('should load the Client Accounts lists for Reseller with correct CTAs @priority=critical', async ({
      page,
    }) => {
      // Check PG All Invites List
      const allInvitesCtaPG = await page.locator(CTA_SELECTORS.CLIENTS_LIST.ALL_INVITES);
      await allInvitesCtaPG.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.ALL_INVITES.LAST_INVITED_ON,
      });
      await expect(
        page.locator(CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON),
      ).not.toBeVisible();

      // Check PG Accepted Invites List
      const acceptedInvitesCtaPG = await page.locator(CTA_SELECTORS.CLIENTS_LIST.ACCEPTED_INVITES);
      await acceptedInvitesCtaPG.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      });
      await expect(
        page.locator(CONTENT_SELECTORS.CLIENTS_LIST.ALL_INVITES.LAST_INVITED_ON),
      ).not.toBeVisible();

      // Check POS List
      const posProductButton = await page.locator(CTA_SELECTORS.PRODUCT_TABS.POS);
      await posProductButton.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      });

      const allInvitesCtaPOS = await page.locator(CTA_SELECTORS.CLIENTS_LIST.ALL_INVITES);
      await allInvitesCtaPOS.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.ALL_INVITES.LAST_INVITED_ON,
      });
      await expect(
        page.locator(CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON),
      ).not.toBeVisible();

      // Check Capital List
      const capitalProductButton = await page.locator(CTA_SELECTORS.PRODUCT_TABS.CAPITAL);
      await capitalProductButton.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.ADDED_ON,
      });
      await expect(
        page.locator(CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON),
      ).not.toBeVisible();
    });
  },
);
