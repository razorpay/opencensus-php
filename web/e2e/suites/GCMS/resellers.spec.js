const { test, expect } = require('@playwright/test');
const { BASE_PATH, getStorageStatePath, routes } = require('testConstants');

test.describe('GCMS resellers @flow=resellers @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).MOBILE_TEST_GCMS_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_RESELLERS);
    await expect(page).toHaveURL(routes.GCMS_RESELLERS);
  });

  test('should be able to view resellers page', async ({ page }) => {
    await expect(await page.getByText('Reseller').first()).toBeVisible();
    await expect(await page.getByText('Ibaco').first()).toBeVisible();
    await expect(await page.getByText('Showing 1 - 25').first()).toBeVisible();
  });

  // TODO: Add this test after more than 25 entries are seeded from the backend
  // test('should be able to fetch next batch of resellers', async ({ page }) => {
  //   await page.getByRole('button', { name: 'next' }).click();
  //   await expect(await page.getByText('Ibaco').first()).toBeVisible();
  //   await expect(await page.getByText('Showing 26 - 50').first()).toBeVisible();
  // });

  test('should show empty message when no reseller is there for a specific status', async ({
    page,
  }) => {
    await page
      .getByRole('textbox', {
        name: 'Reseller Name',
      })
      .fill('abc reseller');
    await page.getByText('Search').click();
    await expect(await page.getByText('There are no resellers yet!!').first()).toBeVisible();
  });

  test('should navigate to reseller details page', async ({ page }) => {
    await page.getByRole('link', { name: 'Ibaco' }).first().click(); //Select first reseller as it has the seed data
    await expect(page).toHaveURL(`${routes.GCMS_RESELLERS}/N91osUDdN9WdO9/programs`); //Reseller id of first reseller
    await expect(await page.getByText('N91osUDdN9WdO9')).toBeVisible(); //Reseller id of first reseller
    await expect(await page.getByText('Account Balance')).toBeVisible();
    await expect(await page.getByText('Thank You Gift Card').first()).toBeVisible();
    await expect(await page.getByText('Ibaco')).toBeVisible();
  });

  test('should navigate to reseller order page', async ({ page }) => {
    await page.getByRole('link', { name: 'Ibaco' }).first().click(); //Select first reseller as it has the seed data
    await expect(page).toHaveURL(`${routes.GCMS_RESELLERS}/N91osUDdN9WdO9/programs`); //Reseller id of first reseller
    await expect(await page.getByText('N91osUDdN9WdO9')).toBeVisible(); //Reseller id of first reseller
    await expect(await page.getByText('Account Balance')).toBeVisible();
    await page.getByRole('link', { name: 'Orders', exact: true }).click();
    await expect(page).toHaveURL(`${routes.GCMS_RESELLERS}/N91osUDdN9WdO9/orders`); //Reseller id of first reseller
    await expect(await page.getByText('Total Quantity')).toBeVisible();
  });

  test('should navigate to reseller account page', async ({ page }) => {
    await page.getByRole('link', { name: 'Ibaco' }).first().click(); //Select first reseller as it has the seed data
    await expect(page).toHaveURL(`${routes.GCMS_RESELLERS}/N91osUDdN9WdO9/programs`); //Reseller id of first reseller
    await expect(await page.getByText('N91osUDdN9WdO9')).toBeVisible(); //Reseller id of first reseller
    await expect(await page.getByText('Account Balance')).toBeVisible();
    await page.getByRole('link', { name: 'Accounts' }).first().click();
    await expect(page).toHaveURL(`${routes.GCMS_RESELLERS}/N91osUDdN9WdO9/accounts`); //Reseller id of first reseller
    await expect(await page.getByText('Account Details')).toBeVisible();
  });
});
