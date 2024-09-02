import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { expectSuccessNotification, clickSkipAndStartBtn } from 'utils';
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
} from './utils';

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

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.PAYMENT_LINKS);
    await clickSkipAndStartBtn({ page });
  });

  // roast test createCompatPaymentLink
  test('should create PL v1 @priority=critical @suite=nocode-P0-automation', async ({ page }) => {
    const productData = paymentLinksUIData.default;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'V1',
    });
    await searchPLAndOpenDetails({ page, referenceId });
    await verifyPLCreated({ page, productData, referenceId, statusToVerify: 'Issued' });
  });

  // roast test cancelCompatLink
  test('should cancel PL v1 @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
    const productData = paymentLinksUIData.default;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'V1',
    });
    await searchPLAndOpenDetails({ page, referenceId });
    await cancelPLCreated({ page });
  });

  // roast test cloneCompatPaymentLink
  test('should clone PL v1 @priority=critical @suite=nocode-P1-automation', async ({ page }) => {
    const productData = paymentLinksUIData.default;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'V1',
    });
    await searchPLAndOpenDetails({ page, referenceId });
    await clonePLCreated({ page, productData, referenceId, isClassic: true });
  });

  // roast test searchByIssuedStatus
  ['Issued'].forEach((statusToVerify) => {
    test(`should search PL v1 with ${statusToVerify} status @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const container = page.locator(COMMON_SELECTORS.tabbedContainer);
      await container.waitFor();
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
  });

  // roast test searchByCompatInvoiceId
  test('should search PL v1 with invoice id @priority=critical @suite=nocode-P1-automation', async ({
    page,
  }) => {
    const container = page.locator(COMMON_SELECTORS.tabbedContainer);
    await container.waitFor();
    await searchAndVerifyByPLId({ container });
  });

  // roast test changeMinDueAmountAndVerifyInCompat
  test('should change and verify min due amount in PL v1 @priority=critical @suite=nocode-P1-automation', async ({
    page,
  }) => {
    const productData = paymentLinksUIData.default;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'V1',
    });
    await searchPLAndOpenDetails({ page, referenceId });

    const minAmountchangeButton = await page.locator(SELECTORS.minAmountchangeButton);
    await minAmountchangeButton.click();
    const newMinAmount = String(productData.first_min_partial_amount / 2);
    await page.getByPlaceholder('Minimum Due Amount').fill(newMinAmount);
    await page.getByRole('button', { name: 'Save' }).click();
    await expectSuccessNotification({
      page,
      notificationText: 'Minimum Due Amount is updated successfully',
    });
    await expect(
      await page.$(`//*[contains(@class, "rzp-whole") and contains(text(), "${newMinAmount}")]`),
    ).toBeDefined();
  });

  // roast test chaneExpiryToNoExpiryVerifyInCompat.
  test('should change and verify expiry to no expiry in PL v1 @priority=critical @suite=nocode-P1-automation', async ({
    page,
  }) => {
    const productData = paymentLinksUIData.default;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'V1',
    });
    await searchPLAndOpenDetails({ page, referenceId });

    const expiryChangeButton = await page.locator(SELECTORS.expiryChangeButton);
    await expiryChangeButton.click();

    await page.locator("//div[contains(text(),'No Expiry')]").click();

    await page.getByRole('button', { name: 'Save' }).click();
    await expectSuccessNotification({
      page,
      notificationText: 'Expire By is updated successfully',
    });
  });

  // roast test verifyPostCancelCompatPLStatus
  test('should cancel and verify PL v1 @priority=critical @suite=nocode-P1-automation', async ({
    page,
  }) => {
    const productData = paymentLinksUIData.default;
    const referenceId = await createPaymentLink({
      page,
      productData,
      type: 'V1',
    });
    await searchPLAndOpenDetails({ page, referenceId });
    await cancelPLCreated({ page });
  });

  // roast test verifyPaymentHistoryForCompatPartiallyPaidPL
  test(`should verify Payment History For Partially Paid PL v1 @priority=critical @suite=nocode-P1-automation`, async ({
    page,
  }) => {
    const statusToVerify = 'Paid';

    const container = page.locator(COMMON_SELECTORS.tabbedContainer);
    await container.waitFor();

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

  // roast test verifyInvoiceIdPresentInCompatPartialPaidPL
  test(`should verify Invoice For Partially Paid PL v1 @priority=critical @suite=nocode-P1-automation`, async ({
    page,
  }) => {
    const statusToVerify = 'Partially Paid';

    const container = page.locator(COMMON_SELECTORS.tabbedContainer);
    await container.waitFor();

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
