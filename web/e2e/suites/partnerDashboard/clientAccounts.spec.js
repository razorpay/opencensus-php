import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { waitForSelectorToBeVisible } from 'utils/common';

import { CTA_SELECTORS, CONTENT_SELECTORS, WELCOME_TEXT_SELECTORS } from './constants';
import { hideInviteFlowFTUXBannersIfPresent } from './utils/ftux';

const { test, expect } = require('@playwright/test');

const TIMEOUT = 20 * 1000;

// Reseller Partner Tests
test.describe.parallel(
  'Test Client Accounts for Reseller @flow=partner-homepage @project=partner-dashboard',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).RESELLER_PARTNER_TEST_LOGIN_STATE,
    });
    test.beforeEach(async ({ page }) => {
      await page.goto(routes.PARTNER_DASHBOARD);
    });
    test('should load the Client Accounts lists for Reseller with correct CTAs @priority=critical', async ({
      page,
    }) => {
      // Waits for all js-bundles to load
      await waitForSelectorToBeVisible(
        { page, selector: WELCOME_TEXT_SELECTORS.RESELLER_WELCOME_TEXT },
        { timeout: TIMEOUT },
      );
      await page.goto(routes.AFFILIATE_ACCOUNTS);
      const pgProductButton = await page.locator(CTA_SELECTORS.PRODUCT_TABS.PG);
      await pgProductButton.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      });

      await hideInviteFlowFTUXBannersIfPresent({ page });

      // Check PG List
      const allInvitesCtaPG = await page.locator(CTA_SELECTORS.CLIENTS_LIST.ALL_INVITES);
      await allInvitesCtaPG.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.LAST_INVITED_ON,
      });
      await expect(
        page.locator(CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON),
      ).not.toBeVisible();

      const acceptedInvitesCtaPG = await page.locator(CTA_SELECTORS.CLIENTS_LIST.ACCEPTED_INVITES);
      await acceptedInvitesCtaPG.click();
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.CLIENTS_LIST.INVITE_ACCEPTED_ON,
      });
      await expect(page.locator(CONTENT_SELECTORS.CLIENTS_LIST.LAST_INVITED_ON)).not.toBeVisible();

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
        selector: CONTENT_SELECTORS.CLIENTS_LIST.LAST_INVITED_ON,
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
