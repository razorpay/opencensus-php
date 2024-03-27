import { CTA_SELECTORS, CONTENT_SELECTORS } from 'partnerDashboard/common/constants';
import {
  navigateToClientAccounts,
  openInviteMerchantModalFromSideHeader,
  openShareReferralLinkModalFromSideHeader,
  testBulkInviteFormValidation,
  testPublicInviteFormValidation,
  testSingleInviteFormValidation,
  testShareReferralLinkModal,
} from 'partnerDashboard/common/utils';
import { getStorageStatePath, BASE_PATH } from 'testConstants';

const { test } = require('@playwright/test');

// Reseller Partner Tests
test.describe.parallel(
  'Test Invite Flows for Reseller Partner @flow=partner-invites @project=partner-dashboard',
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
    test('should load the Single Invite flow for Reseller Partner with correct messages and CTAs @priority=critical', async ({
      page,
    }) => {
      await openInviteMerchantModalFromSideHeader(
        page,
        CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.MODAL_HEADERS.PG,
      );
      await testSingleInviteFormValidation(page);
      await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.CLOSE_BUTTON).click();
    });

    test('should load the Bulk Invite flow for Reseller Partner with correct messages and CTAs @priority=critical', async ({
      page,
    }) => {
      await openInviteMerchantModalFromSideHeader(
        page,
        CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.MODAL_HEADERS.PG,
      );
      await testBulkInviteFormValidation(page);
      await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.CLOSE_BUTTON).click();
    });

    test('should load the Public Invite flow for Reseller Partner with correct messages and CTAs @priority=critical', async ({
      page,
    }) => {
      await openInviteMerchantModalFromSideHeader(
        page,
        CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.MODAL_HEADERS.PG,
      );
      await testPublicInviteFormValidation(page);

      await page.locator(CTA_SELECTORS.SHARE_REFERRAL_LINK_MODAL.CLOSE_BUTTON).click();
    });

    test('should load the Share Referral Link flow for Reseller Partner with correct messages and CTAs @priority=critical', async ({
      page,
    }) => {
      await openShareReferralLinkModalFromSideHeader(page);
      await testShareReferralLinkModal(page, 'Razorpay Payments', true);
      await page.locator(CTA_SELECTORS.SHARE_REFERRAL_LINK_MODAL.CLOSE_BUTTON).click();
    });
  },
);
