import { test, expect } from '@playwright/test';
import { routes, StorageStatePath } from '../../utils/constants';
import { clickSkipAndStartBtn, searchAndVerifyByPLId } from './utils';
import { switchToTestMode } from '../../utils';
import { COMMON_SELECTORS } from '../../utils/selectors';

[
  {
    loginState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
    testDescription: 'Test V2 PL Batch Uploads @flow=payment-links-v2',
    plType: 'V2',
    isTestMode: true,
  },
  {
    loginState: StorageStatePath.EMAIL_LIVE_LOGIN_STATE,
    testDescription: 'Test UPI PL Batch Uploads @flow=payment-links-upi',
    plType: 'UPI',
    isTestMode: false,
  },
  {
    loginState: StorageStatePath.ACTIVATED_NOT_IE_STATE,
    testDescription: 'Test classic PL Batch Uploads @flow=payment-links-v1',
    plType: 'v1',
    isTestMode: true,
  },
].forEach((context) => {
  test.setTimeout(1 * 60 * 1000);
  test.describe.parallel(context.testDescription, () => {
    test.use({
      storageState: context.loginState,
    });

    test.beforeEach(async ({ page }) => {
      if (context.isTestMode) await switchToTestMode({ page });
      await page.goto(routes.PAYMENT_LINKS);
      await clickSkipAndStartBtn({ page });
      await page.getByRole('link', { name: 'Batch Uploads' }).click();
    });

    // roast test verifyv2BatchStatus verifyUPILinkBatchStatus verifyCompatBatchStatus
    test(`should verify status for Batch uploads PL ${context.plType} @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      let isTableEmpty;
      try {
        isTableEmpty = await page.getByRole('heading', { name: 'No Batch Files Found' });
      } catch (err) {
        //
      }
      if (isTableEmpty) {
        console.log('Batch upload table is empty');
        return;
      }
      const firstRow = await page.locator('tbody tr').first();
      await expect(await firstRow.getByText('Processed')).toBeVisible();
    });

    // roast test downloadReportInv2Batch downloadReportInUPIBatch downloadReportInCompatBatch
    test(`should verify report download for Batch uploads PL ${context.plType} @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const firstRow = await page.locator('tbody tr').first();
      const batchId = await firstRow.locator('a').textContent();

      const downloadButton = await firstRow.getByRole('button', {
        name: 'Download',
      });
      await expect(downloadButton).toBeVisible();
      await downloadButton.click();

      const downloadEvent = await page.waitForEvent('download');

      const parsedBatchId = batchId.replace('batch_', '');
      const downloadFileId = downloadEvent.suggestedFilename().split('.')[0];
      expect(downloadFileId).toBe(parsedBatchId);
    });

    // roast test searchByBatchID
    test(`should search and verify by batch Id for Batch uploads PL ${context.plType} @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
      await searchAndVerifyByPLId({ container });
    });
  });
});
