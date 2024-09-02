import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { expect, test } from 'utils/base';
import { navigateTo } from 'utils/common';

import { payments } from './constants';

const searchPaymentId = async (page, paymentId) => {
  await page.getByPlaceholder('Search').fill(paymentId);
  await page.getByRole('button', { name: 'Search' }).click();
};

const openPaymentDetails = async (page, paymentId) => {
  await page
    .getByTestId(`entity-item-row-${paymentId}`)
    .getByRole('button', { name: 'Details' })
    .click();
};

test.describe
  .parallel('Payments transactions (Live Mode) @flow=transactionsV1 @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await navigateTo(page, routes.PAYMENTS);
  });
  test.describe.parallel('Payment details', () => {
    // There are payments which happens via external PGs using Optimizer
    // and these payments are not settled via razorpay
    // In this case we don't want merchant to create transfer,
    // so we are hiding create transfer button for these payments
    test('should not show create transfer button', async ({ page }) => {
      const id = payments.paymentId.authorized.netbanking;
      const paymentsFilter = page.getByTestId('payments-filter');
      await searchPaymentId(paymentsFilter, id);
      await openPaymentDetails(page, id);
      await expect(page.getByText('Transfer', { exact: true })).toBeVisible();
      expect(page.getByRole('button', { name: 'Create transfer' })).not.toBeVisible();
    });
  });
});
