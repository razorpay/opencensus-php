const { test, expect } = require('@playwright/test');
const { BASE_PATH, getStorageStatePath, routes } = require('testConstants');

test.describe('GCMS orders @flow=orders @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).MOBILE_TEST_GCMS_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_ORDERS);
    await expect(page).toHaveURL(routes.GCMS_ORDERS);
  });

  test('should be able to view orders page', async ({ page }) => {
    await expect(await page.getByText('Orders').first()).toBeVisible();
    await expect(await page.getByText('Showing 1 - 25').first()).toBeVisible();
  });

  test('should be able to fetch next batch of orders', async ({ page }) => {
    await page.getByRole('button', { name: 'next' }).click();
    await expect(await page.getByText('Showing 26 - 50').first()).toBeVisible();
  });

  test('should show empty message when no orders is there for a specific status', async ({
    page,
  }) => {
    await page.getByTestId('reseller_name').fill('abc reseller');
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(await page.getByText('There are no orders yet!!').first()).toBeVisible();
  });

  test('should be able to view order details', async ({ page }) => {
    const link = page.locator('tr:nth-child(1)').getByRole('link');
    const orderId = await link.textContent();
    await link.click();
    await expect(page).toHaveURL(`${routes.GCMS_ORDERS}/${orderId}`);
    await expect(await page.getByText(`Order ID: ${orderId}`)).toBeVisible();
  });
});
