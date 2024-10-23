import {
  CTA_SELECTORS,
  CONTENT_SELECTORS,
  INPUT_SELECTORS,
} from 'partnerDashboard/common/constants';
import { loadPartnerDashboardHomePage } from 'partnerDashboard/common/utils';
import { getStorageStatePath, BASE_PATH } from 'testConstants';
import { pageConsoleLog, waitForSelectorToBeVisible } from 'utils/common';

const { test, expect } = require('utils/base');

// Reseller Partner POS Tests
test.describe
  .parallel('Test Partner Manage Team Flows for POS Reseller @flow=partner-homepage @project=partner-dashboard', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).RESELLER_PARTNER_POS_TEST_LOGIN_STATE,
  });
  test.beforeEach(async ({ page }) => {
    await loadPartnerDashboardHomePage(
      page,
      'RESELLER_PARTNER_POS',
      CONTENT_SELECTORS.HOME_PAGE.RESELLER_PARTNER_POS_WELCOME_TEXT,
    );

    // Click on Manage Team navlink
    await page.locator(CTA_SELECTORS.SIDEBAR.PARTNER_NAVLINKS.MANAGE_TEAM).click();

    await waitForSelectorToBeVisible({ page, selector: CONTENT_SELECTORS.MANAGE_TEAM.HEADER });
  });
  test.skip('should load the Partner Manage Team page for Reseller with correct messages and CTAs @priority=critical', async ({
    page,
  }) => {
    try {
      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.MANAGE_TEAM.INVITATIONS.STATIC_INVITE,
      });
      await page.locator(CTA_SELECTORS.MANAGE_TEAM.RESEND_INVITE_FOR_STATIC).click();

      await waitForSelectorToBeVisible({
        page,
        selector: CONTENT_SELECTORS.MANAGE_TEAM.INVITATIONS.INVITE_SUCCESSFUL,
      });
    } catch (e) {
      await pageConsoleLog(page, 'Note: Static email invitation not found. Continuing...');
    }
    await page.locator(CTA_SELECTORS.MANAGE_TEAM.INVITE_NEW_MEMBER).click();
    await page
      .locator(INPUT_SELECTORS.MANAGE_TEAM.INVITE_NEW_MEMBER.EMAIL)
      .fill('Test Invalid Email');
    await page.locator(CTA_SELECTORS.MANAGE_TEAM.SEND_INVITATION).click();

    await expect(
      page.locator(
        CONTENT_SELECTORS.MANAGE_TEAM.INVITE_NEW_MEMBER.VALIDATION_MESSAGES.EMAIL_INVALID,
      ),
    ).toBeVisible();

    await page.locator(CTA_SELECTORS.MANAGE_TEAM.CLOSE_BUTTON).click();
  });
});
