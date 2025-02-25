import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

test.describe('Magic Checkout Settings Tab @project=magic-checkout', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test('should render magic checkout settings page correctly', async ({ page }) => {
    await page.goto(routes.MAGIC_CHECKOUT);
    await expect(await page.getByText('Platform Settings')).toBeVisible();
    await expect(await page.getByTestId('platform-edit-icon')).toBeVisible();
  });
});
