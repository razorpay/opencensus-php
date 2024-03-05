const { test, expect } = require('@playwright/test');
const { BASE_PATH, getStorageStatePath, routes } = require('testConstants');

test.describe('GCMS Funds Reseller Accounts @flow=funds @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).MOBILE_TEST_GCMS_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_FUNDS_RESELLER_ACCOUNTS);
    await expect(page).toHaveURL(routes.GCMS_FUNDS_RESELLER_ACCOUNTS);
  });

  test('should be able to view funds of reseller accounts', async ({ page }) => {
    await expect(await page.getByText('Funds').first()).toBeVisible();
    await expect(await page.getByText('Reseller Name').first()).toBeVisible();
    await expect(await page.getByText('Reseller ID').first()).toBeVisible();
    await expect(await page.getByText('Total Available Fund').first()).toBeVisible();
  });

  test('should be able to search for reseller name', async ({ page }) => {
    await expect(await page.getByText('Reseller ID').first()).toBeVisible();
    await page.getByPlaceholder(/search reseller name/i).fill('Ibaco');
    await page.getByRole('button', { name: /search/i }).click();
    await expect(await page.getByText('NMmhaISjmvNGoo').first()).toBeVisible();
  });

  // test('should be able to fetch next batch of reseller accounts', async ({ page }) => {
  //   await page.getByRole('button', { name: 'next' }).click();
  //   await expect(await page.getByText('Showing 26 - 50').first()).toBeVisible();
  // });

  test('should show empty message when no reseller accont is found for given search query', async ({
    page,
  }) => {
    await expect(await page.getByText('Reseller ID').first()).toBeVisible();
    await page.getByPlaceholder(/search reseller name/i).fill('7878');
    await page.getByRole('button', { name: /search/i }).click();
    await expect(await page.getByText('No Reseller Accounts Found!').first()).toBeVisible();
  });

  test('should clear filters on clear button click', async ({ page }) => {
    await page.getByPlaceholder(/search reseller name/i).fill('Ibaco');
    await page.getByRole('button', { name: /clear/i }).click();
    await expect(await page.getByPlaceholder(/search reseller name/i)).toHaveValue('');
  });
});
