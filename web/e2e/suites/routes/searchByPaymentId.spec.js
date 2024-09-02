import { test } from 'utils/base';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { clickSkipAndStartBtn } from 'utils';

import { getPaymentId, searchPaymentId } from './utils';

test.describe
  .parallel('Test Search Payment ID @flow=route @project=no-code @project=no-code-roast', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.ROUTE_PAYMENTS);
    await clickSkipAndStartBtn({ page });
  });

  // roast test searchByPaymentID
  test('should should search by payment ID @priority=critical @suite=nocode-P0-automation', async ({
    page,
  }) => {
    const paymentId = await getPaymentId({ page });
    await page.goto(routes.ROUTE_PAYMENTS);
    await searchPaymentId({ page, paymentId });
  });
});
