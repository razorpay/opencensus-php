import { getStorageStatePath, routes } from '@dashboard/shared-utils/e2e/constants/paths';
import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
import { BASE_PATH, payments } from '../../constants';
import { expect, test } from '../../utils/base';
import { gotoTransactionDetailsPageById } from '../..//utils';

test.describe
  .parallel('Payments transactions (Live Mode) @flow=transactionsV1 @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await navigateTo(page, routes.PAYMENTS);
    await page.goto(routes.PAYMENTS);
  });
  test.describe.parallel('Payment details', () => {
    // There are payments which happens via external PGs using Optimizer
    // and these payments are not settled via razorpay
    // In this case we don't want merchant to create transfer,
    // so we are hiding create transfer button for these payments
    test('should not show create transfer button', async ({ page }) => {
      const id = payments.paymentId.authorized.netbanking;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      await expect(page.getByText('Transfer', { exact: true })).toBeVisible();
      expect(page.getByRole('button', { name: 'Create transfer' })).not.toBeVisible();
    });
  });
});
