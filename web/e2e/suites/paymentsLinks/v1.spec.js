import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { expectSuccessNotification } from 'utils';
import { test, expect } from 'utils/base';
import { COMMON_SELECTORS } from 'utils/selectors';

import { paymentLinksUIData } from './constants';
import {
  cancelPLCreated,
  clonePLCreated,
  createPaymentLink,
  mockFetchPaymentLinkApi,
  searchAndVerifyByPLId,
  searchAndVerifyByStatus,
  searchPLAndOpenDetails,
  verifyPLCreated,
  statusToKey,
  mockLegacyPlId,
  verifyPaymentHistory,
  getPLv1MockResponse,
  openPaymentLinkDetailsView,
} from './utils';
import { clickSkipAndStartBtn, waitForLoader } from 'utils';

const SELECTORS = {
  expiryChangeButton:
    '//div[contains(., "Expires On")]/div[@class="pair-value"]//button[contains(text(), "Change")]',
  minAmountchangeButton:
    '//div[contains(., "Partial Payment")]/div[@class="pair-value"]//button[contains(text(), "Change")]',
};

test.describe
  .parallel('Test Payments Links V1 @flow=payment-links-v1 @project=no-code @project=no-code-stable @project=no-code-roast @project=payment-links', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_NOT_IE_STATE,
  });

  let createdPaymentLinkId = '';

  test.beforeEach(async ({ page }) => {
    await clickSkipAndStartBtn({ page });
    await page.goto(routes.PAYMENT_LINKS);
  });
  test.describe.serial('Create and Edit Payment Links', () => {
    test('should create PL v1 @priority=critical @suite=nocode-P0-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      const { referenceId, paymentsLinkId } = await createPaymentLink({
        page,
        productData,
        type: 'V1',
      });

      if (paymentsLinkId && referenceId) {
        createdPaymentLinkId = paymentsLinkId;
        await searchPLAndOpenDetails({
          page,
          referenceId,
          paymentsLinkId,
        });
        await verifyPLCreated({ page, productData, referenceId, statusToVerify: 'Issued' });
      } else {
        throw new Error(
          `Error in creating payment links v1 for referenceId ${referenceId} and paymentsLinkId ${paymentsLinkId}`,
        );
      }
    });

    test('should clone PL v1 @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      await openPaymentLinkDetailsView({ page, linkId: createdPaymentLinkId });
      await clonePLCreated({ page, productData, isPaymentLinkV1: true });
    });

    test('should search PL v1 with PL id @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });
      await searchAndVerifyByPLId({ container, paymentLinksId: createdPaymentLinkId });
    });

    test('should change and verify min due amount in PL v1 @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const productData = paymentLinksUIData.default;
      await openPaymentLinkDetailsView({ page, linkId: createdPaymentLinkId });
      const minAmountchangeButton = await page.locator(SELECTORS.minAmountchangeButton);
      await minAmountchangeButton.click();
      const newMinAmount = String(productData.first_min_partial_amount / 2);
      await page.getByPlaceholder('Minimum Due Amount').fill(newMinAmount);
      await page.getByRole('button', { name: 'Save' }).click();
      await expectSuccessNotification({
        page,
        notificationText: 'Minimum Due Amount is updated successfully',
      });
      const locator = await page.locator(
        `//*[contains(@class, "rzp-whole") and contains(text(), "${newMinAmount}")]`,
      );
      await expect(locator).toBeVisible();
    });

    test('should change and verify expiry to no expiry in PL v1 @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      await openPaymentLinkDetailsView({ page, linkId: createdPaymentLinkId });
      const expiryChangeButton = await page.locator(SELECTORS.expiryChangeButton);
      await expiryChangeButton.click();

      const locator = await page.getByText('No Expiry');
      await locator.click();

      await page.getByRole('button', { name: 'Save' }).click();
      await expectSuccessNotification({
        page,
        notificationText: 'Expire By is updated successfully',
      });
    });
  });

  test.describe.parallel('Should cancel  search and verify Payment History for PL V1', () => {
    test('should cancel PL v1 @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
      const productData = paymentLinksUIData.default;
      const { referenceId, paymentsLinkId } = await createPaymentLink({
        page,
        productData,
        type: 'V1',
      });
      await searchPLAndOpenDetails({ page, referenceId, paymentsLinkId });
      await cancelPLCreated({ page });
    });

    test(`should search PL v1 with Issued status @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const statusToVerify = 'Issued';

      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });

      const statusToCheck = statusToKey[statusToVerify];
      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/invoices?skip=0&count=25&status=${statusToCheck}*`,
        mockRespose: getPLv1MockResponse({
          status: statusToCheck,
        }),
        page,
      });

      const firstRowStatus = await searchAndVerifyByStatus({ container, statusToVerify });
      expect(firstRowStatus).toBe(statusToVerify);
    });

    test(`should verify Payment History For Partially Paid PL v1 @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const statusToVerify = 'Paid';

      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });

      const statusToCheck = statusToKey[statusToVerify];
      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/invoices?skip=0&count=25&status=${statusToCheck}*`,
        mockRespose: getPLv1MockResponse({
          status: statusToCheck,
        }),
        page,
      });
      const firstRowStatus = await searchAndVerifyByStatus({ container, statusToVerify });
      expect(firstRowStatus).toBe(statusToVerify);

      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/invoices/${mockLegacyPlId}?*`,
        mockRespose: getPLv1MockResponse({
          status: statusToCheck,
        }),
        page,
      });
      const paymentLink = await verifyPaymentHistory({
        container,
        page,
      });
      await expect(paymentLink).toBeVisible();
    });

    test(`should verify Invoice For Partially Paid PL v1 @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const statusToVerify = 'Partially Paid';

      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await waitForLoader({ page, selector: '[data-testid="spinner"]' });

      const statusToCheck = statusToKey[statusToVerify];
      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/invoices?skip=0&count=25&status=${statusToCheck}*`,
        mockRespose: getPLv1MockResponse({
          status: statusToCheck,
        }),
        page,
      });
      const firstRowStatus = await searchAndVerifyByStatus({ container, statusToVerify });
      expect(firstRowStatus).toBe(statusToVerify);

      await mockFetchPaymentLinkApi({
        targetUrl: `**/merchant/api/test/invoices/${mockLegacyPlId}?*`,
        mockRespose: getPLv1MockResponse({
          status: statusToCheck,
        }),
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
