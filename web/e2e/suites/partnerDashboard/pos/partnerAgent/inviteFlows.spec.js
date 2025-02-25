import {
  CTA_SELECTORS,
  CONTENT_SELECTORS,
  loadClientAccountsDirectly,
  openInviteMerchantModalFromSideHeader,
  openShareReferralLinkModalFromSideHeader,
  testBulkInviteFormValidation,
  testPublicInviteFormValidation,
  testShareReferralLinkModal,
  testSingleInviteFormValidation,
} from '../../common';
import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

// Reseller Partner Tests
test.describe
  .parallel('Test Invite Flows for Reseller POS Agent @flow=partnerships-pos @project=partner-dashboard', () => {
  test.use({
    storageState: getStorageStatePath().RESELLER_PARTNER_AGENT_TEST_LOGIN_STATE,
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
  test.skip('should load the Single Invite flow for Reseller POS Agent with correct messages and CTAs @priority=critical', async ({
    page,
  }) => {
    await openInviteMerchantModalFromSideHeader(
      page,
      CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.MODAL_HEADERS.POS,
    );
    await testSingleInviteFormValidation(page);
    await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.CLOSE_BUTTON).click();
  });

  test.skip('should load the Bulk Invite flow for Reseller POS Agent with correct messages and CTAs @priority=critical', async ({
    page,
  }) => {
    await openInviteMerchantModalFromSideHeader(
      page,
      CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.MODAL_HEADERS.POS,
    );
    await testBulkInviteFormValidation(page);
    await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.CLOSE_BUTTON).click();
  });

  test.skip('should load the Public Invite flow for Reseller POS Agent with correct messages and CTAs @priority=critical', async ({
    page,
  }) => {
    await openInviteMerchantModalFromSideHeader(
      page,
      CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.MODAL_HEADERS.POS,
    );
    await testPublicInviteFormValidation(page);

    await page.locator(CTA_SELECTORS.SHARE_REFERRAL_LINK_MODAL.CLOSE_BUTTON).click();
  });

  test.skip('should load the Share Referral Link flow for Reseller POS Agent with correct messages and CTAs @priority=critical', async ({
    page,
  }) => {
    await openShareReferralLinkModalFromSideHeader(page);
    await testShareReferralLinkModal(page, 'Razorpay POS', true);
    // Other product types should not be visible
    await expect(
      page.locator('div[aria-label="modal"] :text-is("Razorpay Payments")'),
    ).not.toBeVisible();
    await page.locator(CTA_SELECTORS.SHARE_REFERRAL_LINK_MODAL.CLOSE_BUTTON).click();
  });
});
