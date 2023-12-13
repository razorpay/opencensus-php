import { test } from '@playwright/test';

import { validateBatchPaymentPageDetails } from './utils';
import { switchToTestMode } from '../../utils';
import { routes, StorageStatePath, batchPaymentPageData } from '../../utils/constants';
import { clickSkipAndStartBtn } from '../paymentsLinks/utils';

test.setTimeout(2 * 60 * 1000);

test.describe
  .parallel('Test Batch Payments Pages @flow=batch-payment-pages @project=no-code', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await switchToTestMode({ page });
    await page.goto(routes.BATCH_PAYMENT_PAGES);
    await clickSkipAndStartBtn({ page });
  });

  test.skip('should validate batch PP details page', async ({ page }) => {
    await validateBatchPaymentPageDetails({
      page,
      productData: batchPaymentPageData,
    });
  });
});
