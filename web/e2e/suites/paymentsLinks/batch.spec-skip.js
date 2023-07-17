import { expect, test } from '@playwright/test';
import { switchToTestMode } from '../../utils';
import { routes, StorageStatePath } from '../../utils/constants';
import { COMMON_SELECTORS } from '../../utils/selectors';
import { clickSkipAndStartBtn, searchAndVerifyByPLId } from './utils';

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
  test.describe
    .parallel(`${context.testDescription} @project=no-code @project=no-code-roast`, () => {
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
    test.skip(`should verify status for Batch uploads PL ${context.plType} @priority=critical @suite=nocode-P1-automation`, async ({
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
    test.skip(`should verify report download for Batch uploads PL ${context.plType} @priority=critical @suite=nocode-P1-automation`, async ({
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
    test.skip(`should search and verify by batch Id for Batch uploads PL ${context.plType} @priority=critical @suite=nocode-P1-automation`, async ({
      page,
    }) => {
      const container = await page.locator(COMMON_SELECTORS.tabbedContainer);
      await searchAndVerifyByPLId({ container });
    });
  });
});
