import { test } from '@playwright/test';

import { batchPaymentPageData } from './constants';
import {
  validateBatchPaymentPageDetails,
  validateDownloadSampleFile,
  createBatchPaymentPageWithLateFee,
  clickSkipAndStartBtn,
} from './utils';
import { switchToTestMode } from '../../utils';
import { routes, StorageStatePath } from '../../utils/constants';

test.setTimeout(2 * 60 * 1000);

test.describe
  .parallel('Test Batch Payments Pages @flow=batch-payment-pages @project=no-code', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await switchToTestMode({ page });
    await page.goto(routes.PAYMENT_PAGES);
    await clickSkipAndStartBtn({ page });
    await page.goto(routes.BATCH_PAYMENT_PAGES);
  });

  test.skip('should validate batch PP details page', async ({ page }) => {
    await validateBatchPaymentPageDetails({
      page,
      productData: batchPaymentPageData,
    });
  });

  test.skip('should validate download sample file on the batch details page', async ({ page }) => {
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
});
