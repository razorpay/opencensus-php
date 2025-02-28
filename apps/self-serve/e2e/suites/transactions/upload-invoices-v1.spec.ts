import { getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { expect, test } from '../../utils/base';
import { uploadInvoices } from '../../constants';

import {
  assertColumnsVisibility,
  assertPaymentDetails,
  assertSearch,
  navigateToTransactions,
} from '../../utils';

test.describe.parallel('Transactions (Live Mode) @flow=transactionsV1 @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test('should show payments list in Upload Invoices', async ({ page }) => {
    await navigateToTransactions(page);
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

    const response = await page.waitForResponse(`**/merchant/api/live/payments/${allDetails}?**`);
    const res = await response.json();
    const hasB2bExportInvoice = res?.data?.b2b_export_invoice;

    // Assert Payment details row
    const rowDetails = [
      uploadInvoices.paymentId.allDetails,
      'amount-info € - Euro (EUR)',
      /09 Jan 2024/,
      'Bank Transfer',
      'Authorized',
      hasB2bExportInvoice ? /View/ : /Upload/,
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
