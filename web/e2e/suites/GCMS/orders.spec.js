const { test, expect } = require('@playwright/test');

const { StorageStatePath, routes } = require('../../utils/constants');

test.describe('GCMS orders @flow=orders @project=payments', () => {
  test.use({
    storageState: StorageStatePath.MOBILE_TEST_GCMS_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_ORDERS);
    await expect(page).toHaveURL(routes.GCMS_ORDERS);
  });

  test('should be able to view orders page', async ({ page }) => {
    await expect(await page.getByText('Orders').first()).toBeVisible();
    await expect(await page.getByText('20th Jan, 1970').first()).toBeVisible();
    await expect(await page.getByText('Showing 1 - 5').first()).toBeVisible();
  });

  test('should be able to fetch next batch of orders', async ({ page }) => {
    await page.getByRole('button', { name: 'next' }).click();
    await expect(await page.getByText('20th Jan, 1970').first()).toBeVisible();
    await expect(await page.getByText('Showing 6 - 10').first()).toBeVisible();
  });

  test('should show empty message when no orders is there for a specific status', async ({
    page,
  }) => {
    await page.getByTestId('reseller_name').fill('abc reseller');
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(await page.getByText('There are no orders yet!!').first()).toBeVisible();
  });
});
