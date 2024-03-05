import { expect, test } from '@playwright/test';
import { getStorageStatePath, BASE_PATH } from 'testConstants';

import { uploadInvoices } from './constants';
import {
  assertColumnsVisibility,
  assertPaymentDetails,
  assertSearch,
  navigateToTransactions,
} from './utils';

test.describe.parallel('Transactions (Live Mode) @flow=transactionsV1 @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).TRANSACTIONS_LOGIN_STATE,
  });

  test('should show payments list in Upload Invoices', async ({ page }) => {
    await navigateToTransactions(page, 'live');
    await page.getByRole('link', { name: 'Upload Invoices' }).click();

    const columns = [
      'Payment Id',
      'Amount',
      'Created At',
      'Payment Method',
      'Status',
      'Sender Details',
      'Actions',
    ];

    await assertColumnsVisibility(page, columns);

    // Assert Payment ID search
    const allDetails = uploadInvoices.paymentId.allDetails;
    await assertSearch({ page, id: allDetails });

    // Assert Payment details row
    const rowDetails = [
      uploadInvoices.paymentId.allDetails,
      'amount-info € - Euro (EUR)',
      /09 Jan 2024/,
      'Bank Transfer',
      'Authorized',
      /Upload/,
    ];
    await assertPaymentDetails({ page, details: rowDetails });

    // Assert sender name and sender country
    await expect(page.getByRole('cell', { name: 'P95' })).toBeVisible();
    expect(page.getByRole('cell', { name: 'Belgium' })).toBeVisible();

    // Assert sender name not available and sender country
    const noSenderName = uploadInvoices.paymentId.noSenderName;
    await assertSearch({ page, id: noSenderName });
    await expect(page.getByRole('cell', { name: 'Name not available' })).toBeVisible();
    expect(page.getByRole('cell', { name: 'Belgium' })).toBeVisible();

    // Assert sender name and sender country not available
    const noSenderCountry = uploadInvoices.paymentId.noSenderCountry;
    await assertSearch({ page, id: noSenderCountry });
    await expect(page.getByRole('cell', { name: 'P95' })).toBeVisible();
    expect(page.getByRole('cell', { name: 'Country not available' })).toBeVisible();
  });
});
