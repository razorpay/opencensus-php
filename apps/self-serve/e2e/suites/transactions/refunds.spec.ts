import { getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { expect, test } from '../../utils/base';

import { refunds, BASE_PATH } from '../../constants';
import {
  navigateToTransactions,
  gotoTransactionDetailsPageById,
  searchTransactionById,
  assertRefundDetails,
  assertCollapsibleRefundProcessedTimeline,
  assertIssueRefundButton,
  waitForListingLoader,
} from '../../utils';

test.describe
  .parallel('Refunds transactions (Test Mode) @flow=transactions @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  test.describe.parallel('Refunds Landing screen Listing section', () => {
    test.skip('should allow filters & search operations', async ({ page }) => {
      await navigateToTransactions(page);
      await page.getByLabel('view-Refunds-details').click();
      await waitForListingLoader({ page });
      await expect(page.getByRole('link', { name: 'Refunds', exact: true })).toBeVisible();
      const refundsList = page.getByTestId('refunds-list');
      await expect(
        refundsList.getByRole('button', { name: 'Last 7 days', exact: true }),
      ).toBeVisible();
      await expect(refundsList.getByRole('button', { name: 'Status: All' })).toBeVisible();
      await expect(refundsList.getByTestId('search-by-dropdown')).toBeVisible();
      const id = refunds.refundId.fullRefund.processed;
      await searchTransactionById({ page: refundsList, id });
      await expect(refundsList.getByRole('cell', { name: `${id} Copied` })).toBeVisible();
      await expect(refundsList.getByRole('cell', { name: 'pay_MWavaGTL2MpX8U' })).toBeVisible();
      await expect(
        refundsList.getByTestId(`entity-item-row-${id}`).getByText('Processed'),
      ).toBeVisible();
      await expect(
        refundsList.getByTestId(`entity-item-row-${id}`).getByRole('cell', {
          name: 'Razorpay has completed the refund. After this, bank can take 5-7 working days to credit the amount to customer(s) account Processed',
        }),
      ).toBeVisible();

      await refundsList.getByPlaceholder('Search').fill('');
      await refundsList.getByRole('button', { name: 'Status: All' }).click();
      await page.getByRole('menuitem', { name: 'Processing' }).click();
      await expect(page.getByText('No refund in selected duration')).toBeVisible();
      await expect(
        refundsList.getByText('Search using different keywords or time duration'),
      ).toBeVisible();
    });

    test.skip('should show all fields & allow to click on Details link', async ({ page }) => {
      await navigateToTransactions(page);
      await page.getByLabel('view-Refunds-details').click();
      await waitForListingLoader({ page });
      await expect(page.getByRole('link', { name: 'Refunds', exact: true })).toBeVisible();
      const refundsList = page.getByTestId('refunds-list');
      const columns = ['Refund ID', 'Payment ID', 'Created on', 'Amount', 'Status', 'Actions'];
      for await (const column of columns) {
        expect(refundsList.getByRole('columnheader', { name: new RegExp(column) })).toBeVisible();
      }
      const id = refunds.refundId.fullRefund.processed;
      await searchTransactionById({ page: refundsList, id });
      await refundsList
        .getByTestId(`entity-item-row-${id}`)
        .getByRole('button', { name: 'Details' })
        .click();
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByTestId('refund-heading')).toBeVisible();
    });
  });

  test.describe.parallel('Refunds details', () => {
    test.skip('should show "full refund processed" details', async ({ page }) => {
      await navigateToTransactions(page);
      await page.getByLabel('view-Refunds-details').click();
      await waitForListingLoader({ page });
      await expect(page.getByRole('link', { name: 'Refunds', exact: true })).toBeVisible();
      const id = refunds.refundId.fullRefund.processed;
      await gotoTransactionDetailsPageById({
        page,
        id,
        listSelector: 'refunds-list',
        ctaRole: 'button',
      });
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText('pay_MWavaGTL2MpX8U')).toBeVisible();
      await expect(page.getByText('Order ID')).toBeVisible();
      await expect(page.getByText('order_MWavKvMgxgJMud')).toBeVisible();
      await expect(page.getByText('Payment method')).toBeVisible();
      await expect(page.getByText('Net banking( ICIC bank)')).toBeVisible();
      await assertRefundDetails({ page, id, amount: '12.00' });
      await assertCollapsibleRefundProcessedTimeline({ page });
    });

    test.skip('should show "partial refund processed" details', async ({ page }) => {
      await navigateToTransactions(page);
      await page.getByLabel('view-Refunds-details').click();
      await waitForListingLoader({ page });
      await expect(page.getByRole('link', { name: 'Refunds', exact: true })).toBeVisible();
      const id = refunds.refundId.partialRefund.processed;
      await gotoTransactionDetailsPageById({
        page,
        id,
        listSelector: 'refunds-list',
        ctaRole: 'button',
      });
      // await expect(page.getByText('Gross amount₹ 100.00₹ - Indian Rupee (INR)')).toBeVisible();
      // await expect(page.getByText('Net amount₹ 96.58₹ - Indian Rupee (INR)')).toBeVisible();
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText('pay_Lb1dTpEs7VXfkV')).toBeVisible();
      await expect(page.getByText('Order ID')).toBeVisible();
      await expect(page.getByText('order_Lb1XMcfzt13u4D')).toBeVisible();
      await expect(page.getByText('Payment method')).toBeVisible();
      await expect(page.getByText('Net banking( UTIB bank)')).toBeVisible();
      await assertRefundDetails({ page, id, amount: '250.00' });
      await assertCollapsibleRefundProcessedTimeline({ page });
      await assertIssueRefundButton({ page, testId: 'payment-refund-details' });
      await assertIssueRefundButton({ page, testId: 'payment-details-timeline' });
    });

    test.skip('should show "multi-partial refund processed" details', async ({ page }) => {
      await navigateToTransactions(page);
      await page.getByLabel('view-Refunds-details').click();
      await waitForListingLoader({ page });
      await expect(page.getByRole('link', { name: 'Refunds', exact: true })).toBeVisible();
      const id = refunds.refundId.partialRefund.multiPartialRefundProcessed;
      await gotoTransactionDetailsPageById({
        page,
        id,
        listSelector: 'refunds-list',
        ctaRole: 'button',
      });
      // await expect(page.getByText('Gross amount₹ 100.00₹ - Indian Rupee (INR)')).toBeVisible();
      // await expect(page.getByText('Net amount₹ 96.58₹ - Indian Rupee (INR)')).toBeVisible();
      await expect(page.getByText('Details', { exact: true })).toBeVisible();
      await expect(page.getByText('Payment ID')).toBeVisible();
      await expect(page.getByText('pay_MWar3gNs2Tzs18')).toBeVisible();
      await expect(page.getByText('Order ID')).toBeVisible();
      await expect(page.getByText('order_MWaqXhVdkky1xa')).toBeVisible();
      await expect(page.getByText('Payment method')).toBeVisible();
      await expect(page.getByText('Net banking( ICIC bank)')).toBeVisible();
      await assertRefundDetails({ page, id, amount: '1.00' });
      await assertRefundDetails({ page, id: 'rfnd_MWjmoAMfMeV5ix', amount: '1.00' });
      await assertCollapsibleRefundProcessedTimeline({ page });
      await assertCollapsibleRefundProcessedTimeline({ page, count: 1 });
      await assertIssueRefundButton({ page, testId: 'payment-refund-details' });
      await assertIssueRefundButton({ page, testId: 'payment-details-timeline' });
    });
  });
});
