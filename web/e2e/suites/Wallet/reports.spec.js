const { test, expect } = require('@playwright/test');
const { routes, getStorageStatePath, BASE_PATH } = require('testConstants');

test.describe('Test wallet reports @flow=reports @project=payments ', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).WALLET_REPORTS_LOGIN_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.WALLET_REPORTS);
    await expect(page).toHaveURL(routes.WALLET_REPORTS);
  });

  test('should be able to submit a download report request', async ({ page }) => {
    await expect(await page.getByText('Download Report')).toBeVisible();
    await page.getByText('Download Report').click();

    await expect(await page.getByText('Download report for your business')).toBeVisible();
    await page.getByText('Select A Report').click();
    await page.getByRole('option', { name: '1000000Razorpay Report' }).click();

    await page.getByText('What will you receive in this report?').click();
    await page.getByText('Select duration covered in each report').click();
    await page.getByRole('option', { name: 'Today' }).click();

    await page.getByRole('button', { name: 'Start Download' }).click();
    await expect(
      await page.getByText(
        'Report download request submitted successfully. Track your request from the downloads tab.',
      ),
    ).toBeVisible();
  });

  test.skip('should be able to download a report', async ({ page }) => {
    await page.getByText('Date - Newest').nth(0).click();
    await page.getByRole('option', { name: 'Status - Success' }).click();

    await expect(await page.getByText('Wallet Report Org')).toBeVisible();
    await page.getByRole('button', { name: 'Download Report', exact: true }).nth(0).click();

    await expect(await page.getByText('Please wait')).toBeVisible();
    await expect(await page.getByText('File download started.')).toBeVisible();
  });
});
