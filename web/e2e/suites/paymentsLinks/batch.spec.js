import { test, expect } from 'utils/base';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { switchToTestMode, clickSkipAndStartBtn } from 'utils';
import { COMMON_SELECTORS } from 'utils/selectors';

import { searchAndVerifyByPLId } from './utils';

[
  {
    loginState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    testDescription: 'Test V2 PL Batch Uploads @flow=payment-links-v2',
    plType: 'V2',
    isTestMode: false,
  },
  {
    loginState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    testDescription: 'Test UPI PL Batch Uploads @flow=payment-links-upi',
    plType: 'UPI',
    isTestMode: false,
  },
  {
    loginState: getStorageStatePath(BASE_PATH).ACTIVATED_NOT_IE_STATE,
    testDescription: 'Test classic PL Batch Uploads @flow=payment-links-v1',
    plType: 'v1',
    isTestMode: false,
  },
].forEach((context) => {
  test.describe.parallel(
    `${context.testDescription} @project=no-code @project=no-code-roast @project=no-code-stable`,
    () => {
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
    },
  );
});
