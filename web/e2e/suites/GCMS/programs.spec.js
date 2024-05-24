const { BASE_PATH, getStorageStatePath, routes } = require('testConstants');
const { test, expect } = require('utils/base');

test.describe('Test GCMS Programs @flow=programs @project=payments ', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.GCMS_PROGRAMS);
    await expect(page).toHaveURL(routes.GCMS_PROGRAMS);
  });

  test.skip('should be able view gcms programs page', async ({ page }) => {
    await expect(await page.getByText('Programs').first()).toBeVisible();
    await expect(await page.getByText('Inactive Gift Card').first()).toBeVisible();
  });

  test.skip('should be able to navigate to program details page', async ({ page }) => {
    const firstCard = await page
      .locator('div')
      .filter({ hasText: 'Inactive Gift Cardtest description' })
      .first();
    await expect(firstCard).toBeVisible();
    await firstCard.click();

    // Program Details section to be visible
    await expect(await page.getByText('Program Details')).toBeVisible();
    // Basic Details section to be visible
    await expect(await page.getByText('Basic Details')).toBeVisible();
    // Denomination section to be visible
    await expect(await page.getByText('Denomination').first()).toBeVisible();
  });
});
