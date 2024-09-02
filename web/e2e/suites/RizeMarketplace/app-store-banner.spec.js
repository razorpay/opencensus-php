import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { expect, test } from 'utils/base';

test.describe
  .parallel('Rize Marketplace App Store banner @flow=rize-marketplace @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.APP_STORE);
    await expect(page).toHaveURL(routes.APP_STORE);
  });

  test('should navigate to Rize Marketplace Page in new tab on clicking app store banner CTA', async ({
    page,
    context,
  }) => {
    const rizeMarketplacePagePromise = context.waitForEvent('page');

    await page.getByRole('link', { name: /Visit Marketplace/i }).click();
    const rizeMarketplacePage = await rizeMarketplacePagePromise;
    await rizeMarketplacePage.waitForLoadState();

    await expect(rizeMarketplacePage).toHaveURL(routes.RIZE_MARKETPLACE);
    await expect(
      rizeMarketplacePage.getByRole('heading', { name: /marketplace/i }),
    ).toBeInViewport();
  });

  // Skip reason : https://razorpay.slack.com/archives/C061HJGS1CY/p1725005186223989
  test.skip('should open Rize Marketplace Product Page in new tab on clicking product card', async ({
    page,
    context,
  }) => {
    const productCard = page.getByTestId('product-card-tile').first();
    const productUrl = await productCard.getAttribute('href');
    expect(productUrl).toMatch(new RegExp(routes.RIZE_MARKETPLACE));

    const productName = await productCard.getByRole('heading').textContent();

    const productPagePromise = context.waitForEvent('page');
    await productCard.click();
    const productPage = await productPagePromise;
    await productPage.waitForLoadState();

    await expect(productPage).toHaveURL(productUrl);
    await expect(
      productPage.getByRole('heading', { name: productName, level: 1 }),
    ).toBeInViewport();
  });
});
