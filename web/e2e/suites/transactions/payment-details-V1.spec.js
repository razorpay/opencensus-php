import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { expect, test } from 'utils/base';
import { navigateTo } from 'utils/common';

import { payments } from './constants';
import { gotoTransactionDetailsPageById, waitForListingLoader } from './utils';

test.describe
  .parallel('Payments transactions (Live Mode) @flow=transactionsV1 @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await navigateTo(page, routes.PAYMENTS);
    await waitForListingLoader({ page });
  });
  test.describe.parallel('Payment details', () => {
    // There are payments which happens via external PGs using Optimizer
    // and these payments are not settled via razorpay
    // In this case we don't want merchant to create transfer,
    // so we are hiding create transfer button for these payments
    test.skip('should not show create transfer button', async ({ page }) => {
      const id = payments.paymentId.authorized.netbanking;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      await expect(page.getByText('Transfer', { exact: true })).toBeVisible();
      expect(page.getByRole('button', { name: 'Create transfer' })).not.toBeVisible();
    });
  });
});
