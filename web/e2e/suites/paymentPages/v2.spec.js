import { test } from '@playwright/test';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';

import { PAYMENT_PAGES_TYPES, paymentPagesEcommerceData } from './constants';
import { createPaymentPage } from './utils';
import { switchToTestMode } from '../../utils';
import { clickSkipAndStartBtn } from '../paymentsLinks/utils';

test.setTimeout(2 * 60 * 1000);

test.describe.parallel('Test Payments Pages V2 @flow=payment-pages-v2 @project=no-code', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).EMAIL_TEST_LOGIN_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await switchToTestMode({ page });
    await page.goto(routes.PAYMENT_PAGES);
    await clickSkipAndStartBtn({ page });
  });

  // roast test createCompatPaymentLink
  test('should create PP Ecommerce @priority=critical @flow=payment-pages-v2 @project=no-code', async ({
    page,
  }) => {
    await createPaymentPage({
      page,
      productData: paymentPagesEcommerceData.default,
      type: PAYMENT_PAGES_TYPES.storefront,
    });
  });
});
