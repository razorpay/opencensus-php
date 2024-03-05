import { expect, test } from '@playwright/test';
import { BASE_PATH, getStorageStatePath, routes } from 'testConstants';

test.describe.parallel('GST update @flow=account-settings @project=payments', () => {
  test.describe.parallel('Unregistered merchant', () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).EMAIL_LIVE_LOGIN_STATE,
    });

    test('should be able to see GST Details page', async ({ page }) => {
      await page.goto(routes.ACCOUNT_SETTINGS);
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.getByRole('button', { name: 'GST details' }).click();
      await expect(page.getByRole('heading', { name: 'GSTIN information' })).toBeVisible();
      await expect(
        page.getByText(
          'GST addition is not supported for your business type. You can create a new Razorpay Account as a Non- Individual business type and link GST to it.',
        ),
      ).toBeVisible();
    });
  });

  test.describe.parallel('Registered merchant', () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).TRANSACTIONS_LOGIN_STATE,
    });

    test('should be able to see GST Details page', async ({ page }) => {
      await page.goto(routes.ACCOUNT_SETTINGS);
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.getByRole('button', { name: 'GST details' }).click();
      await expect(page.getByText('GST Number', { exact: true })).toBeVisible();
      await expect(page.getByText('26AADCS0472N1Z4')).toBeVisible();
    });
  });
});
