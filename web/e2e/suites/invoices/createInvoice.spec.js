import { test } from '@playwright/test';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { switchToTestMode, clickSkipAndStartBtn } from 'utils';

import { invoiceData } from './constants';
import { createInvoice, searchInvoiceAndOpenDetails } from './utils';
test.describe.parallel('Test Invoices @flow=invoices @project=no-code', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await switchToTestMode({ page });
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
