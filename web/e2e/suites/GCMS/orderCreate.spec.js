const { test, expect } = require('@playwright/test');

const { StorageStatePath, routes } = require('../../utils/constants');

test.describe('GCMS orders create @flow=ordersCreate @project=payments', () => {
  test.use({
    storageState: StorageStatePath.MOBILE_TEST_GCMS_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_RESELLERS);
    await expect(page).toHaveURL(routes.GCMS_RESELLERS);
  });

  test('should navigate to reseller details page and click on create order button', async ({
    page,
  }) => {
    await page.getByRole('link', { name: 'Ibaco' }).first().click(); //Select first reseller as it has the seed data
    await expect(page).toHaveURL(`${routes.GCMS_RESELLERS}/N91osUDdN9WdO9/programs`); //Reseller id of first reseller
    await expect(await page.getByText('N91osUDdN9WdO9')).toBeVisible(); //Reseller id of first reseller
    await expect(await page.getByText('Virtual Account Balance')).toBeVisible();
    await expect(await page.getByText('Thank You Gift Card').first()).toBeVisible();
    await expect(await page.getByText('Ibaco')).toBeVisible();

    await page.getByRole('button', { name: 'Create Order' }).first().click();
    await expect(page).toHaveURL(`${routes.GCMS_ORDERS_CREATE}/programs`); // Programs page
    await expect(await page.getByText('Create Order')).toBeVisible();

    const firstCard = await page.locator('div').filter({ hasText: 'Thank You Gift Card' }).first();
    await expect(firstCard).toBeVisible();
    await firstCard.click();

    await page.getByRole('button', { name: 'Add to cart' }).first().click();

    await page.getByRole('button', { name: 'View Cart' }).first().click();
    await expect(page).toHaveURL(`${routes.GCMS_ORDERS_CREATE}/cart`); // Cart Page
    await expect(await page.getByText('Cart')).toBeVisible();

    await page.getByRole('button', { name: 'Verify & Place Order' }).first().click();
  });
});
