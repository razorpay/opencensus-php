import { getStorageStatePath, routes } from '@dashboard/shared-utils/e2e/constants/paths';
import { expect, test } from '../../utils/base';

import { payments, BASE_PATH } from '../../constants';
import {
  navigateToTransactions,
  gotoTransactionDetailsPageById,
  searchTransactionById,
  assertRefundDetails,
  assertCollapsibleRefundProcessedTimeline,
  // assertIssueRefundButton,
  // assertCollapsibleSettlementRetryTimeline,
  waitForListingLoader,
  // getI18FormattedPhoneNumber,
} from '../../utils';

test.describe
  .parallel('Payments transactions (Test Mode) @flow=transactions @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  test.describe.parallel('Transactions Landing screen Overview section', () => {
    test('should allow navigate to Refunds, Disputes and Failed Payments ', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      await expect(page.getByText('Overview')).toBeVisible();
      await page.getByLabel('view-Refunds-details').click();
      await expect(page).toHaveURL(routes.REFUNDS);
      await expect(page.getByRole('link', { name: 'Refunds', exact: true })).toBeVisible();
      await page.getByRole('button', { name: 'Go Back' }).click();
      await page.getByLabel('view-Disputes-details').click();
      await expect(page).toHaveURL(routes.DISPUTES);
      await expect(page.getByText('Disputes', { exact: true })).toBeVisible();
      await page.getByRole('button', { name: 'Go Back' }).click();
      await page.getByLabel('view-Failed-details').click();
      await expect(page).toHaveURL(routes.FAILED_PAYMENTS);
      await expect(page.getByText('Failed payments', { exact: true })).toBeVisible();
      await page.getByRole('button', { name: 'Go Back' }).click();
      await expect(page.getByRole('link', { name: 'Payments', exact: true })).toBeVisible();
      await expect(page).toHaveURL(routes.PAYMENTS);
    });
  });

  test.describe.parallel('Transactions Landing screen Listing section', () => {
    test('should allow filters & search operations', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      const paymentsList = page.getByTestId('payments-list');
      await expect(paymentsList.getByRole('button', { name: 'Last 7 days' })).toBeVisible();
      await expect(paymentsList.getByRole('button', { name: 'Status: All' })).toBeVisible();
      await expect(paymentsList.getByRole('button', { name: 'Payment method: All' })).toBeVisible();
      await expect(paymentsList.getByTestId('search-by-dropdown')).toBeVisible();
      const id = payments.paymentId.authorized.netbanking;
      await searchTransactionById({ page: paymentsList, id });
      await expect(page.getByRole('cell', { name: new RegExp(id) })).toBeVisible();
      await expect(page.getByRole('cell', { name: '-- Netbanking' })).toBeVisible();
      // await expect(
      //   page.getByRole('cell', { name: getI18FormattedPhoneNumber('+918888888888') }),
      // ).toBeVisible();
      await expect(page.getByTestId(`entity-item-row-${id}`).getByText('Authorized')).toBeVisible();
      await expect(
        page.getByRole('cell', {
          name: "This is amount that was deducted from the customer(s) account after successful authentication. It'll be added to your Razorpay balance after being captured Authorized",
        }),
      ).toBeVisible();
      await paymentsList.getByPlaceholder('Search').fill('pay_unknownId');
      await page.getByRole('button', { name: 'Status: All' }).click();
      await page.getByRole('menuitem', { name: 'Created' }).click();
      await expect(page.getByText('No payment in selected duration')).toBeVisible();
      await expect(
        page.getByText('Search using different keywords or time duration'),
      ).toBeVisible();
    });

    test('should show all fields & allow to click on Details link', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      const columns = [
        'Payment ID',
        'Bank RRN',
        'Customer detail',
        'Created on',
        'Amount',
        'Status',
        'Actions',
      ];
      for await (const column of columns) {
        expect(page.getByRole('columnheader', { name: new RegExp(column) })).toBeVisible();
      }
      const id = payments.paymentId.authorized.netbanking;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      await expect(page).toHaveURL(`${routes.PAYMENTS}/${id}?init_page=Payments`);
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByTestId('refund-heading')).toBeVisible();
    });
  });

  test.describe.parallel('Payments details', () => {
    test('should show "created" payment state details', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      const id = payments.paymentId.created.netbanking;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      // await expect(page.getByText('Net amount₹ 112.00₹ - Indian Rupee (INR)')).toBeVisible();
      // await expect(page.getByText('Gross amount₹ 112.00₹ - Indian Rupee (INR)')).toBeVisible();
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText(id)).toBeVisible();
      await expect(page.getByTestId('refund-heading')).toBeVisible();
      await expect(page.getByText('Only captured payments can be refunded')).toBeVisible();
      await expect(page.getByText('Payment created')).toBeVisible();
      await expect(page.getByText('Payment authorized')).toBeVisible();
      await expect(page.getByText('Amount yet to be authenticated by the bank')).toBeVisible();
    });

    test('should show "authorized" payment state details', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      const id = payments.paymentId.authorized.intlbanktransfer;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      // await expect(page.getByText('Net amount₹ 6,000.00₹ - Indian Rupee (INR)')).toBeVisible();
      // await expect(page.getByText('Gross amount₹ 6,000.00₹ - Indian Rupee (INR)')).toBeVisible();
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText(id)).toBeVisible();
      await expect(page.getByText('Bank RRN')).toBeVisible();
      // await expect(page.getByText('845870741465')).toBeVisible();
      await expect(page.getByText('Payment method')).toBeVisible();
      await expect(page.getByText('Intl Bank Transfer')).toBeVisible();
      await expect(page.getByTestId('refund-heading')).toBeVisible();
      await expect(page.getByText('Only captured payments can be refunded')).toBeVisible();
      await expect(page.getByText('Payment created')).toBeVisible();
      await expect(page.getByText('Payment authorized')).toBeVisible();
      await expect(page.getByText('Payment captured')).toBeVisible();
      await expect(page.getByText('Amount yet to be manually captured')).toBeVisible();
      // await expect(page.getByRole('button', { name: 'Capture payment' })).toBeVisible();
    });

    test('should show "refunded" payment state details', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      const id = payments.paymentId.refunded.netbanking;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText(id)).toBeVisible();
      await expect(page.getByText('Order ID')).toBeVisible();
      await expect(page.getByText('order_MWavKvMgxgJMud')).toBeVisible();
      await expect(page.getByText('Payment method')).toBeVisible();
      await expect(page.getByText('Net banking( ICIC bank)')).toBeVisible();
      await assertRefundDetails({ page, id: 'rfnd_MWayrWkO6fj30Y', amount: '12.00' });
      await assertCollapsibleRefundProcessedTimeline({ page });
    });

    test('should show "captured" payment state details', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      const id = payments.paymentId.captured.card;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      // await expect(page.getByText('Gross amount₹ 6,000.00₹ - Indian Rupee (INR)')).toBeVisible();
      // await expect(page.getByText('Net amount₹ 5,836.80₹ - Indian Rupee (INR)')).toBeVisible();
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText(id)).toBeVisible();
      await expect(page.getByText('Bank RRN')).toBeVisible();
      await expect(page.getByText('Payment method')).toBeVisible();
      await expect(page.getByText('Domestic Credit card (xx1111)')).toBeVisible();
      await expect(page.getByTestId('refund-heading')).toBeVisible();
      // await expect(page.getByText('No refund issued for this payment')).toBeVisible();
      // await expect(page.getByText('Payment created')).toBeVisible();
      // await expect(page.getByText('Payment authorized')).toBeVisible();
      // await expect(page.getByText('Payment captured')).toBeVisible();
      // await assertIssueRefundButton({ page, testId: 'payment-refund-details' });
      // await assertIssueRefundButton({ page, testId: 'payment-details-timeline' });
    });

    test('should show "failed" payment state details', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      const id = payments.paymentId.failed.netbanking;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      // await expect(page.getByText('Gross amount₹ 112.00₹ - Indian Rupee (INR)')).toBeVisible();
      // await expect(page.getByText('Net amount₹ 112.00₹ - Indian Rupee (INR)')).toBeVisible();
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText(id)).toBeVisible();
      await expect(page.getByText('Order ID')).toBeVisible();
      await expect(page.getByText('order_MWawqaTtYcgNXz')).toBeVisible();
      await expect(page.getByText('Payment method')).toBeVisible();
      await expect(page.getByText('Net banking( ICIC bank)')).toBeVisible();
      await expect(page.getByTestId('refund-heading')).toBeVisible();
      await expect(page.getByText('Only captured payments can be refunded')).toBeVisible();
      await expect(page.getByText('Payment created')).toBeVisible();
      // await expect(page.getByText('/^Payment failed$/')).toBeVisible();
      const failureMessage = 'Payment was unsuccessful as it was cancelled by the customer.';
      await expect(
        page.getByTestId('payment-details-overview').getByText(failureMessage),
      ).toBeVisible();
      await expect(page.getByTestId('timeline').getByText(failureMessage)).toBeVisible();
    });

    // the settlement retry timeline is only supported for 6 months from the date of creation of transaction.
    // if this test case is failing please reach out to settlement_dev to create new data for this test case.
    test('should show transaction timeline details', async ({ page }) => {
      await navigateToTransactions(page);
      await waitForListingLoader({ page });
      const id = payments.paymentId.captured.card;
      await gotoTransactionDetailsPageById({ page, id, listSelector: 'payments-list' });
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText(id)).toBeVisible();
      await expect(page.getByText('Payment method')).toBeVisible();
      // await assertCollapsibleSettlementRetryTimeline({ page });
    });
  });
});
