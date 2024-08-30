import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { switchToTestModeShortCircuit, clickSkipAndStartBtn } from 'utils';
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
  searchAndVerifyByPLId,
  searchAndVerifyByPLReferenceId,
  searchAndVerifyByStatus,
  searchPLAndOpenDetails,
  statusToKey,
  verifyPLCreated,
  verifyPaymentHistory,
} from './utils';

test.describe.parallel(
  'Test Payments Links V2 @flow=payment-links-v2 @project=no-code @project=no-code-stable @project=no-code-roast @project=payment-links',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    });

    test.beforeEach(async ({ page }) => {
      await page.goto(routes.PAYMENT_LINKS);
      await switchToTestModeShortCircuit({ page, mid: 'LLkjLdJz4gWVvk' });
      await clickSkipAndStartBtn({ page });
    });

    // roast test createPaymentLinkv2
    test('should create PL @priority=critical @suite=nocode-P0-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      const referenceId = await createPaymentLink({
        page,
        productData,
        type: 'V2',
      });
      await searchPLAndOpenDetails({ page, referenceId });
      await verifyPLCreated({ page, productData, referenceId });
    });

    // roast test cancelPaymentLinkv2
    test('should cancel PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      const referenceId = await createPaymentLink({
        page,
        productData,
        type: 'V2',
      });
      await searchPLAndOpenDetails({ page, referenceId });
      await cancelPLCreated({ page });
    });

    // roast test clonePaymentLinkv2
    test('should clone PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      const referenceId = await createPaymentLink({
        page,
        productData,
        type: 'V2',
      });
      await searchPLAndOpenDetails({ page, referenceId });
      await clonePLCreated({ page, productData });
    });

    // roast test editPaymentLinkv2
    test('should edit PL @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      const referenceId = await createPaymentLink({
        page,
        productData,
        type: 'V2',
      });
      await searchPLAndOpenDetails({ page, referenceId });
      await editPLCreated({ page, productData, referenceId });
    });

    // roast test searchByCreatedPLStatus searchByCancelledStatusv2Enabled searchByExpiredStatusv2Enabled searchByPaidStatusv2Enabled searchByPartiallyPaidStatusv2Enabled
    const allStatuses = ['Created', 'Paid', 'Partially Paid', 'Cancelled', 'Expired'];
    allStatuses.forEach((statusToVerify) => {
      test(`should search PL with ${statusToVerify} status @priority=critical @suite=nocode-P1-automation`, async ({
        page,
      }) => {
        const container = page.locator(COMMON_SELECTORS.tabbedContainer);
        await container.waitFor();
        const statusToCheck = statusToKey[statusToVerify];
        const targetUrl = `**/merchant/api/test/payment_links?skip=0&count=25&status=${statusToCheck}*`;
        await mockFetchPaymentLinkApi({
          targetUrl,
          mockRespose: {
            payment_links: [getPLv2MockResponse({ status: statusToCheck })],
          },
          page,
        });
        const firstRowStatus = await searchAndVerifyByStatus({ container, statusToVerify });
        expect(firstRowStatus).toBe(statusToVerify);
      });
    });

    // roast test searchByPaymentLinkId
    test('should search PL with payment link id @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await container.waitFor();
      await searchAndVerifyByPLId({ container });
    });

    // roast test searchByReceiptNov2Enabled
    test('should search PL with receipt id @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const productData = paymentLinksUIData.default;
      const referenceId = await createPaymentLink({
        page,
        productData,
        type: 'V2',
      });
      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await container.waitFor();
      await searchAndVerifyByPLReferenceId({ container, referenceId });
    });

    // roast test verifyPaymentHistoryForPaidPL
    test(`should verify Payment History For Paid PL @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const statusToVerify = 'Paid';
      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await container.waitFor();
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

    // roast test verifyPaymentHistoryForPartiallyPaidPL
    test(`should verify Payment History For Partially Paid PL @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const statusToVerify = 'Partially Paid';

      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await container.waitFor();

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
  },
);
