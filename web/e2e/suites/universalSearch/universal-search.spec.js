import { expect, test } from '@playwright/test';
import { generateRandomText } from '../../utils';
import { routes, StorageStatePath } from '../../utils/constants';
import {
  COPY_TEXTS,
  SEARCH_TERMS_WITH_EXPECTED_RESULTS,
  SEARCHABLE_ENTITIES,
  ENTITY_SEARCH_KEYS,
  getEntitySearchResultsRoutes,
} from './constants';
import { assertSearchResults, getSearchResultsEl } from './utils';

test.describe.parallel('Test universal search @flow=universal-search @project=payments', () => {
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
    SEARCHABLE_ENTITIES.forEach(async (entity) => {
      await expect(searchResults.getByText(`in: ${entity}`)).toBeVisible();
    });
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
    await searchResults.getByText('Reminders').nth(0).click();
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

    const productMatchingByTitle = await searchResults.getByText('Offers').nth(0);
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

test.describe.parallel('Test universal search @flow=universal-entity-search', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });

  test('should show entity search results for entity id search @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    const searchQuery = 'pay_1234567891012131';
    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();
    await expect(searchResults.getByText('in: Payments')).toBeVisible();
    await expect(searchResults.getByText('in: Refunds')).toBeVisible();
    await expect(searchResults.getByText('in: Disputes')).toBeVisible();

    await searchResults.getByText('in: Refunds').click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.REFUND_PAYMENT_ID),
    );
  });

  test('should show entity search results for ph number search @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = '8600720041';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: `+91${searchQuery}`,
    });
    await expect(searchResults).toBeVisible();
    await expect(searchResults.getByText('in: Payments')).toBeVisible();

    await searchResults.getByText('in: Payments').click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.PAYMENT_PH_NUMBER),
    );
  });

  test('should show entity search results for email search @priority=normal', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = 'aakash@test.com';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });
    await expect(searchResults).toBeVisible();
    await expect(searchResults.getByText('in: Payments')).toBeVisible();

    await searchResults.getByText('in: Payments').click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.PAYMENT_EMAIL),
    );
  });

  test('should show entity search results for entity status search @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = 'created';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();
    await expect(searchResults.getByText('in: Settlements')).toBeVisible();
    await expect(searchResults.getByText('in: Orders')).toBeVisible();

    await searchResults.getByText('in: Orders').click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.ORDER_STATUS),
    );
  });

  test('should show entity search results for UTR search in Settlements entity @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = '1234567891112'; // example utr number

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();
    const settlementsSearchResult = searchResults.getByText('in: Settlements');
    await expect(settlementsSearchResult).toBeVisible();

    await settlementsSearchResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.SETTLEMENTS_UTR_NUMBER),
    );
  });

  test('should show entity search results for title search in paymentpages @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = 'Sample payment page';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();
    const paymentPageSearchResult = searchResults.getByText('in: PaymentPages');
    await expect(paymentPageSearchResult).toBeVisible();

    await paymentPageSearchResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.PAYMENT_PAGES_TITLE),
    );
  });

  test('should show entity search results for paymentlink url search @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = 'https://rzp.io/i/fdsfsdf';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();
    const paymentLinkSearchResult = searchResults.getByText('in: PaymentLinks');
    await expect(paymentLinkSearchResult).toBeVisible();

    await paymentLinkSearchResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.PAYMENT_LINKS_URL),
    );
  });

  test('should list all entities for no search query match @priority=normal', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = 'xyzabc';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();

    SEARCHABLE_ENTITIES.forEach(async (entity) => {
      await expect(searchResults.getByText(`in: ${entity}`)).toBeVisible();
    });

    await searchResults.getByText('in: Orders').click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.ORDER_RANDOM_QUERY),
    );
  });
});
