import { BASE_PATH, getStorageStatePath, routes } from 'testConstants';
import { expect, test } from 'utils/base';

test.describe.parallel('GST update @flow=account-settings @project=payments', () => {
  test.describe.parallel('Unregistered merchant', () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_NOT_IE_STATE,
    });

    test('should be able to see GST Details page', async ({ page }) => {
      await page.goto(routes.ACCOUNT_SETTINGS);
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.getByRole('button', { name: 'GST details' }).click();
      await expect(page.getByText('GSTIN information', { exact: true })).toBeVisible();

      await expect(
        page.getByText(
          'GST addition is not supported for your business type. You can create a new Razorpay Account as a Non- Individual business type and link GST to it.',
        ),
      ).toBeVisible();
    });
  });

  test.describe.parallel('Registered merchant', () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
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
