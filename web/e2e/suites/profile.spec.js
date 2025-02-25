import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

test.describe('Account & settings > Profile Test @flow=account-settings @project=payments @project=payments-roast', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test('should navigate to profile section in account and settings', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await page.getByRole('link', { name: 'Account & Settings' }).click();
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
  });

  test('should render merchant profile section', async ({ page }) => {
    await page.goto(routes.ACCOUNT_SETTINGS);
    await expect(await page.getByText('Your profile')).toBeVisible();
    await expect(
      await page.getByText('Merchant ID', {
        exact: true,
      }),
    ).toBeVisible();
    await expect(await page.getByText('2-step verification')).toBeVisible();
    await expect(await page.getByText('Phone number')).toBeVisible();
    await expect(await page.getByText('Login email')).toBeVisible();
    await expect(await page.getByText('Password')).toBeVisible();
  });
});
