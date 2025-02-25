import {
  routes,
  test,
  expect,
  getStorageStatePath,
  generateRandomText,
} from '@libs/shared-qsuite/playwright';

import {
  COPY_TEXTS,
  SEARCH_TERMS_WITH_EXPECTED_RESULTS,
  SEARCHABLE_ENTITIES,
  ENTITY_SEARCH_KEYS,
  getEntitySearchResultsRoutes,
} from './constants';
import { assertSearchResults, findInSearchResultsEl, getSearchResultsEl } from './utils';

test.describe.parallel('Test universal search @flow=universal-search @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
  });

  test('should show universal search box and open search results on focus @priority=normal', async ({
    page,
  }) => {
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

test.describe
  .parallel('Test universal search @flow=universal-entity-search @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
  });

  test('should show entity search results for entity id search @priority=normal', async ({
    page,
  }) => {
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

    const refundsResult = await findInSearchResultsEl({ searchResults, entity: 'Refunds' });
    await refundsResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.REFUND_PAYMENT_ID),
    );
  });

  test('should show entity search results for ph number search @priority=normal', async ({
    page,
  }) => {
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

    const entities = ['Payments', 'PaymentLinks', 'SmartCollect', 'Customer', 'QRcode'];

    entities.forEach(async (entity) => {
      await expect(searchResults.getByText(`in: ${entity}`)).toBeVisible();
    });

    const paymentsResults = await findInSearchResultsEl({ searchResults, entity: 'Payments' });

    await paymentsResults.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.PAYMENT_PH_NUMBER),
    );
  });

  test('should show entity search results for email search @priority=normal', async ({ page }) => {
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

    const entities = ['Payments', 'PaymentLinks', 'Accounts', 'SmartCollect', 'Customer', 'QRcode'];
    entities.forEach(async (entity) => {
      await expect(searchResults.getByText(`in: ${entity}`)).toBeVisible();
    });
    // await expect(searchResults.getByText('in: Payments')).toBeVisible();

    const paymentsResult = await findInSearchResultsEl({ searchResults, entity: 'Payments' });

    await paymentsResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.PAYMENT_EMAIL),
    );
  });

  test('should show entity search results for entity status search @priority=normal', async ({
    page,
  }) => {
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

    const entities = ['Settlements', 'Transfers', 'Orders', 'PaymentLinks'];
    entities.forEach(async (entity) => {
      await expect(searchResults.getByText(`in: ${entity}`)).toBeVisible();
    });

    await searchResults.getByText('in: Orders').click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.ORDER_STATUS),
    );
  });

  test('should show entity search results for UTR search in Settlements entity @priority=normal', async ({
    page,
  }) => {
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
    const paymentLinkSearchResult = await findInSearchResultsEl({
      searchResults,
      entity: 'PaymentLinks',
    });

    await expect(paymentLinkSearchResult).toBeVisible();

    await paymentLinkSearchResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.PAYMENT_LINKS_URL),
    );
  });

  test('should show entity search results for QRCode status search @priority=normal', async ({
    page,
  }) => {
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = 'active';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();
    const paymentPagesSearchResult = searchResults.getByText('in: PaymentPages');
    const paymentButtonsSearchResult = searchResults.getByText('in: PaymentButtons');
    const qrCodesSearchResult = searchResults.getByText('in: QRcode');

    await expect(paymentPagesSearchResult).toBeVisible();
    await expect(paymentButtonsSearchResult).toBeVisible();
    await expect(qrCodesSearchResult).toBeVisible();

    await qrCodesSearchResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.QRCODE_STATUS),
    );
  });

  test('should show entity search results for Accounts email search @priority=normal', async ({
    page,
  }) => {
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = 'test@gm.om';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();
    const paymentsSearchResult = searchResults.getByText('in: Payments');
    const paymentLinksSearchResult = searchResults.getByText('in: PaymentLinks');
    const accountsSearchResult = searchResults.getByText('in: Accounts');

    await expect(paymentsSearchResult).toBeVisible();
    await expect(paymentLinksSearchResult).toBeVisible();
    await expect(accountsSearchResult).toBeVisible();

    await accountsSearchResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.ACCOUNTS_EMAIL),
    );
  });

  test('should show entity search results for Reversals transferId search @priority=normal', async ({
    page,
  }) => {
    const searchBox = await page.locator("input[name='search']");
    await expect(searchBox).toBeVisible();
    await searchBox.focus();
    const searchQuery = 'trf_12345678912345';

    const searchResults = await getSearchResultsEl({
      page,
      searchBox,
      searchTerm: searchQuery,
    });

    await expect(searchResults).toBeVisible();
    const transfersSearchResult = searchResults.getByText('in: Transfers');
    const reversalsSearchResult = searchResults.getByText('in: Reversals');

    await expect(transfersSearchResult).toBeVisible();
    await expect(reversalsSearchResult).toBeVisible();

    await reversalsSearchResult.click();
    await expect(page).toHaveURL(
      getEntitySearchResultsRoutes(searchQuery, ENTITY_SEARCH_KEYS.REVERSALS_TRANSFER_ID),
    );
  });

  test('should list all entities for no search query match @priority=normal', async ({ page }) => {
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
