const { test, expect } = require('@playwright/test');
const { StorageStatePath, routes } = require('../../utils/constants');

test.describe('Magic Checkout Settings Tab @project=magic-checkout', () => {
  test.use({
    storageState: StorageStatePath.MAGIC_CHECKOUT_STATE,
  });

  test('should render magic checkout settings page correctly', async ({ page }) => {
    await page.goto(routes.MAGIC_CHECKOUT);
    await expect(await page.getByText('Platform Settings')).toBeVisible();
    await expect(await page.getByTestId('platform-edit-icon')).toBeVisible();
  });
});
