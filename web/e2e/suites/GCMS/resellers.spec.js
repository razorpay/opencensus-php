const { test, expect } = require('@playwright/test');

const { StorageStatePath, routes } = require('../../utils/constants');

test.describe('GCMS resellers @flow=resellers @project=payments', () => {
  test.use({
    storageState: StorageStatePath.MOBILE_TEST_GCMS_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_RESELLERS);
    await expect(page).toHaveURL(routes.GCMS_RESELLERS);
  });

  test('should be able to view resellers page', async ({ page }) => {
    await expect(await page.getByText('Reseller').first()).toBeVisible();
    await expect(await page.getByText('Ibaco').first()).toBeVisible();
    await expect(await page.getByText('Showing 1 - 5').first()).toBeVisible();
  });

  test('should be able to fetch next batch of resellers', async ({ page }) => {
    await page.getByRole('button', { name: 'next' }).click();
    await expect(await page.getByText('Ibaco').first()).toBeVisible();
    await expect(await page.getByText('Showing 6 - 10').first()).toBeVisible();
  });

  test('should show empty message when no reseller is there for a specific status', async ({
    page,
  }) => {
    await page.getByTestId('merchant_name').fill('abc reseller');
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(await page.getByText('There are no resellers yet!!').first()).toBeVisible();
  });

  test('should navigate to reseller details page', async ({ page }) => {
    await page.getByRole('link', { name: 'Ibaco' }).first().click(); //Select first reseller as it has the seed data
    await expect(page).toHaveURL(`${routes.GCMS_RESELLERS}/N91osUDdN9WdO9/programs`); //Reseller id of first reseller
    await expect(await page.getByText('N91osUDdN9WdO9')).toBeVisible(); //Reseller id of first reseller
    await expect(await page.getByText('Virtual Account Balance')).toBeVisible();
    await expect(await page.getByText('Thank You Gift Card').first()).toBeVisible();
    await expect(await page.getByText('Ibaco')).toBeVisible();
  });
});
