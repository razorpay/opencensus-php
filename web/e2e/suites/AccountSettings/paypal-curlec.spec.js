import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

test.skip('Test paypal instrument for Curlec org @flow=account-settings @country=MY', () => {
  test.use({
    storageState: getStorageStatePath().CURLEC_TEST_CAW_LOGIN_STATE,
  });

  test('should show paypal and not international Cards', async ({ page }) => {
    await page.goto(routes.ACCOUNT_SETTINGS);
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);

    const button = await page.locator(`button:has-text("International payments")`);
    await expect(button).toBeVisible();
    await button.click();

    await expect(page.getByText('International Payments', { exact: true })).toBeVisible();
    const doc = await page.locator('a:has-text("Know More about payment methods")');
    await expect(doc).toBeVisible();
    expect(doc).toHaveAttribute('href', 'https://curlec.com/docs/payments/payment-methods/');

    await expect(page.getByText('PayPal', { exact: true })).toBeVisible();
    await expect(
      page.getByText('Accept International Payments using PayPal on Curlec Checkout', {
        exact: true,
      }),
    ).toBeVisible();

    await expect(page.getByText('International Cards', { exact: true })).not.toBeVisible();

    const linkBtn = await page.locator('button:has-text("Link Account")');
    await expect(linkBtn).toBeVisible();
  });
});
