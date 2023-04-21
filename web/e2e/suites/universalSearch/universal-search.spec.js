import { expect, test } from '@playwright/test';
import { generateRandomText } from '../../utils';
import { routes, StorageStatePath } from '../../utils/constants';
import { COPY_TEXTS, SEARCH_TERMS_WITH_EXPECTED_RESULTS } from './constants';
import { assertSearchResults, getSearchResultsEl } from './utils';

test.describe.parallel('Test universal search @flow=universal-search', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });

  test('should show universal search box and open search results on focus @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: generateRandomText(5),
    });
    await expect(searchResults).toBeVisible();

    // click outside searchbox to focus out
    await page.locator('.layout').click();
    await expect(searchResults).not.toBeVisible();
  });

  test('popular searches and no results found @priority=normal', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();

    let searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: generateRandomText(2),
    });
    await expect(searchResults.getByText(COPY_TEXTS.POPULAR_SEARCHES)).toBeVisible();

    searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: generateRandomText(4),
    });
    await expect(searchResults.getByText(COPY_TEXTS.POPULAR_SEARCHES)).not.toBeVisible();

    searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: 'gibberish',
    });
    await expect(searchResults).toBeVisible();
    await expect(searchResults.getByText(COPY_TEXTS.NO_RESULTS_FOUND_TEXT.TITLE)).toBeVisible();
    await expect(searchResults.getByText(COPY_TEXTS.NO_RESULTS_FOUND_TEXT.TEXT)).toBeVisible();
  });

  test('should navigate to product on search result click @priority=normal', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();

    let searchResults = await page.getByTestId('universal-search-results');
    await expect(searchResults).toBeVisible();

    await searchResults.getByText('Account & Settings').click();
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);

    searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: 'reminders',
    });
    await expect(searchResults).toBeVisible();
    await searchResults.getByText('Reminders').click();
    await expect(page).toHaveURL(routes.REMINDERS);
  });

  test('should give higher rank to search result by title over keyword @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: 'offers',
    });
    await expect(searchResults).toBeVisible();

    const productMatchingByTitle = await searchResults.getByText('Offers');
    const productMatchingByKeyword = await searchResults.getByText('Checkout rewards');

    const titleProductBox = await productMatchingByTitle.boundingBox();
    const keywordProductBox = await productMatchingByKeyword.boundingBox();

    expect(titleProductBox.y).toBeLessThan(keywordProductBox.y);
  });

  test('should show appropriate search results @priority=normal', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();

    for (const { searchTerm, expectedSearchResults } of SEARCH_TERMS_WITH_EXPECTED_RESULTS) {
      // eslint-disable-next-line no-await-in-loop
      await assertSearchResults({
        page,
        searchBox,
        searchTerm,
        expectedSearchResults,
      });
    }
  });
});
