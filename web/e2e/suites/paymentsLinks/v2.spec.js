import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { test, expect } from 'utils/base';
import { COMMON_SELECTORS } from 'utils/selectors';

import { paymentLinksUIData } from './constants';
import {
  cancelPLCreated,
  clonePLCreated,
  createPaymentLink,
  editPLCreated,
  getPLv2MockResponse,
  mockFetchPaymentLinkApi,
  mockPlId,
  openPaymentLinkDetailsView,
  searchAndVerifyByPLId,
  searchAndVerifyByPLReferenceId,
  searchAndVerifyByStatus,
  searchPLAndOpenDetails,
  statusToKey,
  verifyPLCreated,
  verifyPaymentHistory,
} from './utils';
import { clickSkipAndStartBtn, waitForLoader } from 'utils';

test.describe
  .parallel('Test Payments Links V2 @flow=payment-links-v2 @project=no-code @project=no-code-stable @project=no-code-roast @project=payment-links', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  let createdPaymentLinkId = '';
  let createdReferenceId = '';

  test.beforeEach(async ({ page }) => {
    await clickSkipAndStartBtn({ page });
    await page.goto(routes.PAYMENT_LINKS);
  });
  test.describe.serial('Create and Edit Payment Links V2', () => {
    test('should create PL @priority=critical @suite=nocode-P0-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      const { referenceId, paymentsLinkId } = await createPaymentLink({
        page,
        productData,
        type: 'V2',
      });

      if (paymentsLinkId && referenceId) {
        createdPaymentLinkId = paymentsLinkId;
        createdReferenceId = referenceId;
        await searchPLAndOpenDetails({
          page,
          referenceId,
          paymentsLinkId,
        });
        await verifyPLCreated({ page, productData, referenceId });
      } else {
        throw new Error(
          `Error in creating payment links v1 for referenceId ${referenceId} and paymentsLinkId ${paymentsLinkId}`,
        );
      }
    });

    test('should clone PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      await openPaymentLinkDetailsView({ page, linkId: createdPaymentLinkId });
      await clonePLCreated({ page, productData });
    });

    test('should edit PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      await openPaymentLinkDetailsView({ page, linkId: createdPaymentLinkId });
      const { referenceId } = await editPLCreated({ page, productData });
      createdReferenceId = referenceId;
    });

    test('should search PL with payment link id @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });
      await searchAndVerifyByPLId({ container, paymentLinksId: createdPaymentLinkId });
    });

    test.skip('should search PL with receipt id @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });
      await searchAndVerifyByPLReferenceId({ container, referenceId: createdReferenceId });
    });
  });

  test.describe.parallel('Should cancel and verify Payment History for PL V2', () => {
    test('should cancel PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      const { referenceId, paymentsLinkId } = await createPaymentLink({
        page,
        productData,
        type: 'V2',
      });
      await searchPLAndOpenDetails({ page, referenceId, paymentsLinkId });
      await cancelPLCreated({ page });
    });

    test.skip(`should verify Payment History For Paid PL @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const statusToVerify = 'Paid';
      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });
      const statusToCheck = statusToKey[statusToVerify];
      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/payment_links?skip=0&count=25&status=${statusToCheck}*`,
        mockRespose: {
          payment_links: [getPLv2MockResponse({ status: statusToCheck })],
        },
        page,
      });
      const firstRowStatus = await searchAndVerifyByStatus({ container, statusToVerify });
      expect(firstRowStatus).toBe(statusToVerify);

      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/payment_links/${mockPlId}?*`,
        mockRespose: getPLv2MockResponse({ status: statusToCheck }),
        page,
      });
      const paymentLink = await verifyPaymentHistory({
        container,
        page,
      });
      await expect(paymentLink).toBeVisible();
    });

    test.skip(`should verify Payment History For Partially Paid PL @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const statusToVerify = 'Partially Paid';

      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });

      const statusToCheck = statusToKey[statusToVerify];
      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/payment_links?skip=0&count=25&status=${statusToCheck}*`,
        mockRespose: {
          payment_links: [getPLv2MockResponse({ status: statusToCheck })],
        },
        page,
      });
      const firstRowStatus = await searchAndVerifyByStatus({ container, statusToVerify });
      expect(firstRowStatus).toBe(statusToVerify);

      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/payment_links/${mockPlId}?*`,
        mockRespose: getPLv2MockResponse({ status: statusToCheck }),
        page,
      });
      const paymentLink = await verifyPaymentHistory({
        container,
        page,
      });
      await expect(paymentLink).toBeVisible();
    });
  });
});
