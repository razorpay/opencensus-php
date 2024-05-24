import {
  CTA_SELECTORS,
  CONTENT_SELECTORS,
  INPUT_SELECTORS,
} from 'partnerDashboard/common/constants';
import { expect } from 'utils/base';
import { waitForSelectorToBeVisible } from 'utils/common';

const { resolve } = require('path');

export const testShareReferralLinkModal = async (page, productTitle, isKycAssistEnabled) => {
  // Click on given product
  await page.getByText(productTitle, { exact: true }).click();
  if (isKycAssistEnabled) {
    // Select KYC Assist
    await page.locator(CTA_SELECTORS.SHARE_REFERRAL_LINK_MODAL.KYC_ASSIST.YES).click();
  }
  // Try to copy the link
  await page.getByRole('button', { name: 'Copy Link' }).click();
};

export const testPublicInviteFormValidation = async (page) => {
  // Open Public Links Tab
  await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.TABS.PUBLIC_LINKS).click();
  // Select KYC Assist
  await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.KYC_ASSIST.YES).click();
  // Try to copy the link
  await page.getByRole('button', { name: 'Copy Link' }).click();
};

export const testBulkInviteFormValidation = async (page) => {
  await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.TABS.BULK_INVITE).click();

  const bulkInviteInputs = INPUT_SELECTORS.INVITE_MERCHANT_MODAL.BULK_INVITE;
  await page.setInputFiles(
    bulkInviteInputs.FILE,
    resolve(__dirname, '..', 'files', 'sample_invite_submerchant_batch.xlsx'),
  );
  await waitForSelectorToBeVisible({
    page,
    selector: CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.VALIDATION_MESSAGES.TWO_CONTACTS_IDENTIFIED,
  });
  await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.FOOTER_BUTTONS.NEXT_STEP).click();
  await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.FOOTER_BUTTONS.SEND_INVITES).click();
  await waitForSelectorToBeVisible({
    page,
    selector: CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.VALIDATION_MESSAGES.KYC_ASSIST_REQUIRED,
  });
};

export const testSingleInviteFormValidation = async (page) => {
  await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.TABS.SINGLE_INVITE).click();

  const singleInviteInputs = INPUT_SELECTORS.INVITE_MERCHANT_MODAL.SINGLE_INVITE;
  await page.locator(singleInviteInputs.EMAIL).fill('Test Invalid Email');
  await page.locator(singleInviteInputs.CONTACT_NO).fill('911111111');
  await expect(
    page.locator(CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.VALIDATION_MESSAGES.EMAIL_INVALID),
  ).toBeVisible();
  await page.locator(CTA_SELECTORS.INVITE_MERCHANT_MODAL.FOOTER_BUTTONS.NEXT_STEP).click();
  await waitForSelectorToBeVisible({
    page,
    selector: CONTENT_SELECTORS.INVITE_MERCHANT_MODAL.VALIDATION_MESSAGES.NAME_REQUIRED,
  });
};

export const openShareReferralLinkModalFromSideHeader = async (page) => {
  await page.locator(CTA_SELECTORS.SIDE_HEADER.SHARE_REFERRAL_LINK).click();

  await waitForSelectorToBeVisible({
    page,
    selector: CONTENT_SELECTORS.SHARE_REFERRAL_LINK_MODAL.HEADER,
  });
};

export const openInviteMerchantModalFromSideHeader = async (page, modalSelector) => {
  await page.locator(CTA_SELECTORS.SIDE_HEADER.ADD_NEW_CLIENTS).click();
  await waitForSelectorToBeVisible({
    page,
    selector: modalSelector,
  });
};
