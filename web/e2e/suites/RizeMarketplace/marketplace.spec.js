import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { expect, test } from 'utils/base';

test.describe.parallel(
  'Rize Marketplace landing page @flow=rize-marketplace @project=payments',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    });

    test.beforeEach(async ({ page }) => {
      await page.goto(routes.RIZE_MARKETPLACE);
      await expect(page).toHaveURL(routes.RIZE_MARKETPLACE);
    });

    test('should perform search and clear search', async ({ page }) => {
      const latestProduct = page.getByTestId('product-card-list').first();

      const searchInput = page.getByRole('textbox', { name: /search/i });
      await searchInput.fill('Leadmonk');
      await searchInput.press('Enter');

      await page.getByRole('progressbar').waitFor({ state: 'hidden' });

      const productCard = page.getByTestId('product-card-list').first();
      await expect(productCard.getByRole('heading')).toContainText('Leadmonk');

      await searchInput.clear();
      await expect(latestProduct).toBeInViewport();
    });

    test('should filter products', async ({ page }) => {
      const filter = page.locator('[data-blade-component="chip-label"]').nth(3);
      await filter.click();
      const filterValue = await filter.getByRole('checkbox').getAttribute('value');

      await page.getByRole('progressbar').waitFor({ state: 'hidden' });

      const productCards = await page.getByTestId('product-card-list').all();

      await Promise.all(
        productCards.map(async (productCard) => {
          const productCategory = productCard.getByText(filterValue.toUpperCase(), { exact: true });
          await expect(productCategory).toHaveCount(1);
        }),
      );
    });

    test('should paginate when "load more" button is clicked', async ({ page }) => {
      // Wait for the first product card to be attached, then query all of them
      await page.getByTestId('product-card-list').first().waitFor({ state: 'attached' });
      const numInitialProducts = (await page.getByTestId('product-card-list').all()).length;

      const loadMoreButton = page.getByRole('button', { name: /load more/i });
      await loadMoreButton.scrollIntoViewIfNeeded();

      await loadMoreButton.click();

      await expect(loadMoreButton).not.toBeInViewport();

      const spinner = page.getByRole('progressbar');
      expect(spinner).toBeInViewport();

      await spinner.waitFor({ state: 'hidden' });
      await page.waitForLoadState('networkidle');
      const numUpdatedProducts = (await page.getByTestId('product-card-list').all()).length;
      expect(numUpdatedProducts).toBeGreaterThan(numInitialProducts);
    });

    test('should open Rize Marketplace Product Page on clicking product card', async ({ page }) => {
      /**
       * @param {import('utils/base').Locator} productCard
       */
      const testProductCard = async (productCard) => {
        const productUrl = await productCard.getAttribute('href');
        expect(productUrl).toMatch(new RegExp(routes.RIZE_MARKETPLACE));

        const productName = await productCard.getByRole('heading').textContent();
        await productCard.click();

        await expect(page).toHaveURL(productUrl);
        await expect(page.getByRole('heading', { name: productName, level: 1 })).toBeInViewport();
      };

      const productCardTile = page.getByTestId('product-card-tile').first();
      const productCardList = page.getByTestId('product-card-list').first();

      await testProductCard(productCardTile);
      await page.goBack();
      await testProductCard(productCardList);
    });

    test('should open rize homepage in new tab when "know more" button in footer is clicked', async ({
      page,
      context,
    }) => {
      const rizeFooter = page.getByTestId('rize-footer');
      await rizeFooter.scrollIntoViewIfNeeded();

      const rizeHomepagePromise = context.waitForEvent('page');

      await rizeFooter.getByRole('link', { name: /know more/i }).click();
      const rizeMarketplacePage = await rizeHomepagePromise;
      await expect(rizeMarketplacePage).toHaveURL('https://razorpay.com/rize');
    });
  },
);
