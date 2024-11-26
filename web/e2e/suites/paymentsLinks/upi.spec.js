import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { test, expect } from 'utils/base';
import { COMMON_SELECTORS } from 'utils/selectors';

import { upiLinksData } from './constants';
import {
  cancelPLCreated,
  clonePLCreated,
  createPaymentLink,
  openPaymentLinkDetailsView,
  searchAndVerifyByPLId,
  searchPLAndOpenDetails,
  verifyPLCreated,
} from './utils';
import { waitForLoader, clickSkipAndStartBtn } from 'utils';

const SELECTORS = {
  REFERENCE_ID_CHANGE_BTN:
    '//div[contains(., "Reference Id")]/div[@class="pair-value"]//button[contains(text(), "Change")]',
};

test.describe
  .parallel('Test UPI Payment Links @flow=payment-links-upi @project=no-code @project=no-code-stable @project=no-code-roast @project=payment-links', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  let createdPaymentLinkId = '';

  test.beforeEach(async ({ page }) => {
    await clickSkipAndStartBtn({ page });
    await page.goto(routes.PAYMENT_LINKS);
  });

  test.describe.serial('Create and Clone UPI Payment Links', () => {
    test('should create UPI PL @priority=critical @suite=nocode-P0-automation', async ({
      page,
    }) => {
      const productData = upiLinksData.paymentLinkWithAllParams;
      const { referenceId, paymentsLinkId } = await createPaymentLink({
        page,
        productData,
        type: 'UPI',
      });

      if (paymentsLinkId && referenceId) {
        createdPaymentLinkId = paymentsLinkId;
        await searchPLAndOpenDetails({
          page,
          referenceId,
          paymentsLinkId,
        });
        await verifyPLCreated({ page, productData, referenceId });
      } else {
        throw new Error(
          `Error in creating payment links UPI for referenceId ${referenceId} and paymentsLinkId ${paymentsLinkId}`,
        );
      }
    });

    test('should clone UPI PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = upiLinksData.paymentLinkWithAllParams;
      await openPaymentLinkDetailsView({ page, linkId: createdPaymentLinkId });
      await clonePLCreated({ page, productData });
    });

    test('should search UPI PL with payment link id @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });
      await searchAndVerifyByPLId({ container, paymentLinksId: createdPaymentLinkId });
    });
  });

  test.describe.parallel('Should cancel and verify Payment History for PL UPI', () => {
    test.skip('should verify Field Modification Disabled in UPI PL @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const productData = upiLinksData.paymentLinkWithAllParams;
      const { referenceId, paymentsLinkId } = await createPaymentLink({
        page,
        productData,
        type: 'UPI',
      });
      await searchPLAndOpenDetails({ page, referenceId, paymentsLinkId });
      const changeButtonBeforeCancel = await page.locator(SELECTORS.REFERENCE_ID_CHANGE_BTN);
      await expect(changeButtonBeforeCancel).toBeVisible();
      await cancelPLCreated({ page, productData, referenceId });
      const changeButtonAfterCancel = await page.locator(SELECTORS.REFERENCE_ID_CHANGE_BTN);
      await expect(changeButtonAfterCancel).not.toBeVisible();
    });
  });
});
