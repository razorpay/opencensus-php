import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { switchToTestMode } from 'utils';
import { test } from 'utils/base';
import { COMMON_SELECTORS } from 'utils/selectors';

import { paymentLinksUIData } from './constants';
import {
  cancelPLCreated,
  clickSkipAndStartBtn,
  clonePLCreated,
  createPaymentLink,
  editPLCreated,
  searchAndVerifyByPLId,
  searchAndVerifyByPLReferenceId,
  searchAndVerifyByStatus,
  searchPLAndOpenDetails,
  verifyPaymentHistory,
  verifyPLCreated,
} from './utils';

test.setTimeout(2 * 60 * 1000);
test.describe.parallel(
  'Test Payments Links V2 @flow=payment-links-v2 @project=no-code @project=no-code-roast',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).EMAIL_TEST_LOGIN_STATE,
    });

    test.beforeEach(async ({ page }) => {
      await switchToTestMode({ page });
      await page.goto(routes.PAYMENT_LINKS);
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
    ['Created', 'Paid', 'Partially Paid', 'Cancelled', 'Expired'].forEach((statusToVerify) => {
      test(`should search PL with ${statusToVerify} status @priority=critical @suite=nocode-P1-automation`, async ({
        page,
      }) => {
        const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
        await searchAndVerifyByStatus({ container, statusToVerify });
      });
    });

    // roast test searchByPaymentLinkId
    test('should search PL with payment link id @priority=critical @suite=nocode-P1-automation', async ({
      page,
    }) => {
      const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
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
      const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
      await searchAndVerifyByPLReferenceId({ container, referenceId });
    });

    // roast test verifyPaymentHistoryForPaidPL
    test(`should verify Payment History For Paid PL @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
      await searchAndVerifyByStatus({ container, statusToVerify: 'Paid' });
      await verifyPaymentHistory({ page, container, isPartialPaid: false });
    });

    // roast test verifyPaymentHistoryForPartiallyPaidPL
    test(`should verify Payment History For Partially Paid PL @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
      await searchAndVerifyByStatus({ container, statusToVerify: 'Partially Paid' });
      await verifyPaymentHistory({ page, container, isPartialPaid: true });
    });
  },
);
