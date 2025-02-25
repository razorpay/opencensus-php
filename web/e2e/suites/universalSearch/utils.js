import { expect } from '@libs/shared-qsuite/playwright';

export const getSearchResultsEl = async ({ page, searchBox, searchTerm }) => {
  await searchBox.clear();
  await searchBox.type(searchTerm);
  const searchResults = await page.getByTestId('universal-search-results');
  return searchResults;
};

export const assertSearchResults = async ({
  page,
  searchBox,
  searchTerm,
  expectedSearchResults,
}) => {
  const searchResults = await getSearchResultsEl({
    page,
    searchBox,
    searchTerm,
  });
  for (const searchResult of expectedSearchResults) {
    // eslint-disable-next-line no-await-in-loop
    const searchResultEl = await searchResults.getByText(searchResult, {
      exact: true,
    });
    expect(searchResultEl).toBeDefined();
  }
};

export const findInSearchResultsEl = async ({ searchResults, entity }) => {
  // tag isn't clickable. Need to click another DOM node inside one of the parents
  const foundResult = await searchResults
    .getByText(`in: ${entity}`)
    .locator('..')
    .locator('..')
    .locator('div')
    .first();
  return foundResult;
};
