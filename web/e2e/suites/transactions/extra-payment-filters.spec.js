import moment from 'moment';
import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

import { navigateToTransactions, waitForListingLoader } from './utils';

test.describe.parallel('Payment Filters(Test Mode)  @flow=transactions @project=payments', () => {
  test.use({
    storageState: getStorageStatePath('test').POS_ORDER_DETAILS_LOGIN_STATE,
  });

  test.describe.parallel('Transactions Filters', () => {
    test('should allow payment method filter', async ({ page }) => {
      const startDate = moment().add(-7, 'd').startOf('day').unix();
      const endDate = moment().endOf('day').unix();

      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      await page.getByRole('button', { name: 'All Filters' }).click();
      await page.getByPlaceholder('Select Payment Method').click();
      await page.getByRole('option', { name: 'UPI' }).click();
      await page.getByRole('button', { name: 'Apply' }).click();
      await expect(page).toHaveURL(`${routes.PAYMENTS}?from=${startDate}&to=${endDate}&method=upi`);
    });
  });
});
