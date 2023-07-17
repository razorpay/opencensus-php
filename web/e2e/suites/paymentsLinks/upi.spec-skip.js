import { test, expect } from '@playwright/test';
import { routes, StorageStatePath } from '../../utils/constants';
import { upiLinksData } from './constants';
import {
  cancelPLCreated,
  clickSkipAndStartBtn,
  clonePLCreated,
  createPaymentLink,
  searchAndVerifyByPLId,
  searchPLAndOpenDetails,
  verifyPLCreated,
} from './utils';
import { COMMON_SELECTORS } from '../../utils/selectors';

const SELECTORS = {
  REFERENCE_ID_CHANGE_BTN:
    '//div[contains(., "Reference Id")]/div[@class="pair-value"]//button[contains(text(), "Change")]',
};

test.setTimeout(2 * 60 * 1000);
test.describe
  .parallel('Test UPI Payment Links @flow=payment-links-upi @project=no-code @project=no-code-roast', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_LIVE_LOGIN_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.PAYMENT_LINKS);
    await clickSkipAndStartBtn({ page });
  });

  // roast test createPaymentLinkv2
  test('should create UPI PL @priority=critical @suite=nocode-P0-automation', async ({ page }) => {
    const productData = upiLinksData.paymentLinkWithAllParams;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'UPI',
    });
    await searchPLAndOpenDetails({ page, referenceId });
    await verifyPLCreated({ page, productData, referenceId });
  });

  // roast test cloneUPIPaymentLink
  test('should clone UPI PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
    const productData = upiLinksData.paymentLinkWithAllParams;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'UPI',
    });
    await searchPLAndOpenDetails({ page, referenceId });
    await clonePLCreated({ page, productData, referenceId });
  });

  // roast test cancelUPIPaymentLink
  test('should cancel UPI PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
    const productData = upiLinksData.paymentLinkWithAllParams;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'UPI',
    });
    await searchPLAndOpenDetails({ page, referenceId });
    await cancelPLCreated({ page, productData, referenceId });
  });

  // roast test verifyPostCancelUPIPLStatus
  test('should verify Field Modification Disabled in UPI PL @priority=critical @suite=nocode-P1-automation', async ({
    page,
  }) => {
    const productData = upiLinksData.paymentLinkWithAllParams;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'UPI',
    });
    await searchPLAndOpenDetails({ page, referenceId });
    const changeButtonBeforeCancel = await page.locator(SELECTORS.REFERENCE_ID_CHANGE_BTN);
    await expect(changeButtonBeforeCancel).toBeVisible();
    await cancelPLCreated({ page, productData, referenceId });
    const changeButtonAfterCancel = await page.locator(SELECTORS.REFERENCE_ID_CHANGE_BTN);
    await expect(changeButtonAfterCancel).not.toBeVisible();
  });

  // roast test searchByPaymentLinkId
  test('should search UPI PL with payment link id @priority=critical @suite=nocode-P1-automation', async ({
    page,
  }) => {
    const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
    await searchAndVerifyByPLId({ container });
  });
});
