import { expect, test } from '@playwright/test';
import { StorageStatePath, routes } from '../../utils/constants';

test.describe.parallel('GST update @flow=account-settings @project=payments', () => {
  test.describe.parallel('Unregistered merchant', () => {
    test.use({
      storageState: StorageStatePath.EMAIL_LIVE_LOGIN_STATE,
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
      storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
    });

    test('should be able to see GST Details page', async ({ page }) => {
      await page.goto(routes.ACCOUNT_SETTINGS);
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.getByRole('button', { name: 'GST details' }).click();
      await expect(page.getByRole('heading', { name: 'GST details' })).toBeVisible();
      await expect(page.getByText('GST Number', { exact: true })).toBeVisible();
      await expect(page.getByText('01AADCB1234M1ZX')).toBeVisible();
      await expect(page.getByText('Registered Address')).toBeVisible();
      await expect(
        page.getByText('145232cv sgfyfgvwehv efw, Central Delhi, DL, 110001'),
      ).toBeVisible();
      await expect(page.getByText('Status')).toBeVisible();
      await expect(page.getByText('Active')).toBeVisible();
      await expect(page.getByText("Razorpay's GST Number")).toBeVisible();
      await expect(page.getByText('29AAGCR4375J1ZU')).toBeVisible();
      await expect(page.getByTestId('tooltip-interactive-wrapper')).toBeVisible();
    });
  });
});
