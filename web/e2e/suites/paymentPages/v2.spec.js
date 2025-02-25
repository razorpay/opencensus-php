import {
  routes,
  test,
  getStorageStatePath,
  clickSkipAndStartBtn,
} from '@libs/shared-qsuite/playwright';

import { PAYMENT_PAGES_TYPES, paymentPagesEcommerceData } from './constants';
import { createPaymentPage } from './utils';

test.describe.parallel('Test Payments Pages V2 @flow=payment-pages-v2 @project=no-code', () => {
  test.use({
    storageState: getStorageStatePath('test').ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
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
