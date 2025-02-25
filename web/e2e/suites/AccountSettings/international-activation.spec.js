import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

test.describe
  .parallel('Test International Activation flow when merchant is eligible for international @flow=international-activation', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test('should show international activation form', async ({ page }) => {
    await page.goto(routes.INTERNATIONAL_PAYMENTS);

    await page.getByRole('button', { name: 'Request for international cards' }).click();

    await expect(page.getByText('BUSINESS DETAILS', { exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Next' }).click();

    await expect(page.getByText('SUPPORTING DETAILS', { exact: true })).toBeVisible();

    expect(await page.getByLabel('Yes').isChecked()).toBeTruthy();

    await page.getByRole('combobox').first().selectOption('Bank Statement');

    await expect(page.getByText('Bank Statement (Last 60 days)')).toBeVisible();

    await page.getByRole('combobox').nth(1).selectOption('Forward inward remittance statement');

    await expect(
      page.locator('span').filter({ hasText: 'Forward inward remittance statement' }),
    ).toBeVisible();

    expect(await page.getByRole('checkbox').isChecked()).toBeFalsy();

    await page.getByRole('button', { name: 'Previous' }).click();

    await expect(
      page.getByText('Choose product(s) to collect international payments on'),
    ).toBeVisible();
  });
});
