const { BASE_PATH, getStorageStatePath, routes } = require('testConstants');
const { test, expect } = require('utils/base');

test.describe('GCMS orders @flow=orders @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_ORDERS);
    await expect(page).toHaveURL(routes.GCMS_ORDERS);
  });

  test('should be able to view orders page', async ({ page }) => {
    await expect(await page.getByText('Orders').first()).toBeVisible();
    await expect(await page.getByText('Showing 1 - 25').first()).toBeVisible();
  });

  test('should show empty message when no orders is there for a specific status', async ({
    page,
  }) => {
    await page
      .getByRole('textbox', {
        name: 'Order ID',
      })
      .fill('abc reseller');
    await page.getByText('Search').click();
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
