import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

const COUPON_PRODUCT_ROUTE = '/app/rize-marketplace/small-frozen-car-momm6';
const LINK_PRODUCT_ROUTE = '/app/rize-marketplace/sleek-steel-computer-momun';
const INVALID_PRODUCT_ROUTE = '/app/rize-marketplace/iwejoifwjfie';

/**
 * @param {import('utils/base').Page} page
 * @param {string} url
 */
const gotoURL = async (page, url) => {
  await page.goto(url);
  await expect(page).toHaveURL(url);
};

const testApplyHereLink = async (page, context) => {
  const availPagePromise = context.waitForEvent('page');

  await page.getByRole('link', { name: /apply here/i }).click();
  await availPagePromise;
};

test.describe
  .parallel('Rize Marketplace listing page @flow=rize-marketplace @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
    permissions: ['clipboard-read'],
  });

  test.skip('should show coupon code deals', async ({ page, context }) => {
    await gotoURL(page, COUPON_PRODUCT_ROUTE);
    await page.getByRole('button', { name: /avail deal/i }).click();

    await page.getByRole('button', { name: /copy code/i }).click();

    const couponCode = await page.getByTestId('large-deal-card-coupon-code').textContent();
    const copiedValue = await page.evaluate(() => navigator.clipboard.readText());

    expect(copiedValue).toBe(couponCode);
    await testApplyHereLink(page, context);
  });

  test.skip('should show avail link only deals', async ({ page, context }) => {
    await gotoURL(page, LINK_PRODUCT_ROUTE);
    await page.getByRole('button', { name: /avail deal/i }).click();

    await testApplyHereLink(page, context);
  });

  test.skip('should open Rize public profile of cofounder in a new tab', async ({
    page,
    context,
  }) => {
    await gotoURL(page, COUPON_PRODUCT_ROUTE);
    const cofounderCard = page.getByTestId('cofounder-card').first();

    const cofounderPagePromise = context.waitForEvent('page');

    await cofounderCard.click();

    const cofounderPage = await cofounderPagePromise;
    await expect(cofounderPage).toHaveURL(/rize(.*?)\/profile\//);
  });

  // https://razorpay.slack.com/archives/C061HJGS1CY/p1729088007855769
  test.skip('should open Rize Marketplace Product Page on clicking a similar product card', async ({
    page,
  }) => {
    await gotoURL(page, LINK_PRODUCT_ROUTE);
    const similarProductCard = page.getByTestId('product-card-tile').first();

    const productUrl = await similarProductCard.getAttribute('href');
    expect(productUrl).toMatch(new RegExp(routes.RIZE_MARKETPLACE));

    const productName = await similarProductCard.getByRole('heading').textContent();

    await similarProductCard.click();

    await expect(page).toHaveURL(productUrl);
    await expect(page.getByRole('heading', { name: productName, level: 1 })).toBeInViewport();
  });

  test('should show error message and link when product does not exist', async ({ page }) => {
    await gotoURL(page, INVALID_PRODUCT_ROUTE);

    await expect(page.getByText(/could not load product details/i)).toBeInViewport();
    const backToMarketplaceLink = page.getByRole('button', { name: /back to marketplace/i });

    await expect(backToMarketplaceLink).toBeInViewport();
    await backToMarketplaceLink.click();

    expect(page).toHaveURL(routes.RIZE_MARKETPLACE);
  });
});
