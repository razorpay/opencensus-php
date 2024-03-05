const { test, expect } = require('@playwright/test');
const { BASE_PATH, getStorageStatePath, routes } = require('testConstants');

test.describe('GCMS brand transactions @flow=brandTransactions @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).MOBILE_TEST_GCMS_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_BRAND_TRANSACTIONS);
    await expect(page).toHaveURL(routes.GCMS_BRAND_TRANSACTIONS);
  });

  test('should be able to view brand transactions page', async ({ page }) => {
    await expect(await page.getByText('Funds').first()).toBeVisible();
    await expect(await page.getByText('All Time').first()).toBeVisible();
  });

  // test('should be able to fetch next batch of transactions', async ({ page }) => {
  //   await page.getByRole('button', { name: 'next' }).click();
  //   await expect(await page.getByText('All Time').first()).toBeVisible();
  //   await expect(await page.getByText('Showing 26 - 50').first()).toBeVisible();
  // });

  test('should show empty message when no transactions is there for a specific reference Id', async ({
    page,
  }) => {
    await page.getByTestId('reference_id').fill('test reference Id');
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(await page.getByText('There are no transactions yet').first()).toBeVisible();
  });
});
