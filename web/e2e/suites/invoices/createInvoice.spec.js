import {
  routes,
  test,
  getStorageStatePath,
  clickSkipAndStartBtn,
} from '@libs/shared-qsuite/playwright';

import { invoiceData } from './constants';
import { createInvoice, searchInvoiceAndOpenDetails } from './utils';

test.describe.parallel('Test Invoices @flow=invoices @project=no-code', () => {
  test.use({
    storageState: getStorageStatePath('test').ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.INVOICES);
    await clickSkipAndStartBtn({ page });
  });

  test('should create invoice', async ({ page }) => {
    const referenceId = await createInvoice({
      page,
      invoiceData,
    });
    await searchInvoiceAndOpenDetails({ page, referenceId });
  });
});
