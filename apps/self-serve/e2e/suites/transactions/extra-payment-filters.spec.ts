import { expect, test } from '@playwright/test';
import moment from 'moment';
import { routes, getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { BASE_PATH } from '../../constants';

import { navigateToTransactions } from '../../utils';

test.describe.parallel('Payment Filters(Test Mode)  @flow=transactions @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).POS_ORDER_DETAILS_LOGIN_STATE,
  });

  test.describe.parallel('Transactions Filters', () => {
    test('should allow payment method filter', async ({ page }) => {
      const startDate = moment().add(-7, 'd').startOf('day').unix();
      const endDate = moment().endOf('day').unix();

      await navigateToTransactions(page);
      await page.getByRole('button', { name: 'All Filters' }).click();
      await page.getByPlaceholder('Select Payment Method').click();
      await page.getByRole('option', { name: 'UPI' }).click();
      await page.getByRole('button', { name: 'Apply' }).click();
      await expect(page).toHaveURL(`${routes.PAYMENTS}?from=${startDate}&to=${endDate}&method=upi`);
    });
  });
});
