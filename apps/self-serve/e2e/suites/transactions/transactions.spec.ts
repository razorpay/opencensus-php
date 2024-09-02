import { getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { expect, test } from '../../utils/base';
import { BASE_PATH } from '../../constants';

import { navigateToTransactions } from '../../utils';

test.describe.parallel('Transactions (Test Mode) @flow=transactions @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  test('should show Batch Upload modal in Batch Payments', async ({ page }) => {
    await navigateToTransactions(page);
    await page.getByRole('link', { name: 'Batch Payments' }).click();
    const batchUploadButton = page.getByRole('button', { name: 'Click here to upload' });
    await expect(batchUploadButton).toBeVisible();
    await batchUploadButton.click();
    await expect(page.getByTestId('batch-upload-modal')).toBeVisible();
  });

  test('should show Batch Refunds modal in Batch Refunds', async ({ page }) => {
    await navigateToTransactions(page);
    await page.getByLabel('view-Refunds-details').click();
    await page.getByRole('link', { name: 'Batch Refunds' }).click();
    const batchUploadButton = page.getByRole('button', { name: 'Click here to upload' });
    await expect(batchUploadButton).toBeVisible();
    await batchUploadButton.click();
    await expect(page.getByRole('heading', { name: 'Batch Upload', exact: true })).toBeVisible();
  });

  test('should show orders list and details in Orders', async ({ page }) => {
    await navigateToTransactions(page);
    await page.getByRole('link', { name: 'Orders', exact: true }).click();
    const columns = ['Order Id', 'Amount', 'Attempts', 'Receipt', 'Created At', 'Status'];
    for await (const column of columns) {
      await expect(page.getByRole('cell', { name: new RegExp(column) })).toBeVisible();
    }
    await expect(page.getByRole('heading', { name: 'No Orders Found!' })).toBeVisible();
  });

  test('should show dispute list and details in Disputes', async ({ page }) => {
    await navigateToTransactions(page);
    await page.getByLabel('view-Disputes-details').click();
    expect(page.getByText('Disputes', { exact: true })).toBeVisible();
    expect(page.getByRole('link', { name: 'Guide to Dispute' })).toBeVisible();
    const columns = ['Dispute Id', 'Amount', 'Type', 'Respond By', 'Created At', 'Status'];
    for await (const column of columns) {
      await expect(page.getByRole('cell', { name: new RegExp(column) })).toBeVisible();
    }
    await expect(
      page.getByText('No disputes found for the selected duration and criteria!'),
    ).toBeVisible();
  });

  test('should show invoice list and details in Invoices', async ({ page }) => {
    await navigateToTransactions(page);
    await page.getByRole('link', { name: 'Invoices', exact: true }).click();
    const columns = [
      'Payment Id',
      'Invoice Number',
      'Amount',
      'Created At',
      'Payment Method',
      'Status',
      'Actions',
    ];
    for await (const column of columns) {
      await expect(page.getByRole('cell', { name: new RegExp(column) })).toBeVisible();
    }
    await page.locator('input[name="id"]').fill('pay_MWm0faxRp7Whwh');
    await page.getByRole('button', { name: 'Search' }).click();
    const details = [
      'pay_MWm0faxRp7Whwh',
      'amount-info ₹ - Indian Rupee (INR)',
      /31 Aug 2023/,
      'Upi',
      'Authorized',
      /Upload Invoice/,
    ];
    for await (const detail of details) {
      await expect(page.getByRole('cell', { name: detail })).toBeVisible();
    }
    await page.getByRole('button', { name: 'Bulk Upload' }).click();
    await expect(page.getByText('Upload Invoice Bill')).toBeVisible();
  });
});
