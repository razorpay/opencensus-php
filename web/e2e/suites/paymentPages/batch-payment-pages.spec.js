import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { test } from 'utils/base';

import { batchPaymentPageData } from './constants';
import {
  validateBatchPaymentPageDetails,
  validateDownloadSampleFile,
  createBatchPaymentPageWithLateFee,
} from './utils';
import { switchToTestMode } from '../../utils';
import { clickSkipAndStartBtn } from 'utils';

test.describe.parallel(
  'Test Batch Payments Pages @flow=batch-payment-pages @project=no-code-stable',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    });

    test.beforeEach(async ({ page }) => {
      await switchToTestMode({ page });
      await page.goto(routes.PAYMENT_PAGES);
      await clickSkipAndStartBtn({ page });
      await page.waitForTimeout(1000);
      await page.goto(routes.BATCH_PAYMENT_PAGES);
    });

    test('should validate batch PP details page', async ({ page }) => {
      await validateBatchPaymentPageDetails({
        page,
        productData: batchPaymentPageData,
      });
    });

    test('should validate download sample file on the batch details page', async ({ page }) => {
      await validateDownloadSampleFile({
        page,
        productData: batchPaymentPageData,
      });
    });

    test('should throw mandatory amount field error while creating batch PP with late fee', async ({
      page,
    }) => {
      await createBatchPaymentPageWithLateFee({
        page,
        productData: batchPaymentPageData,
      });
    });
  },
);
