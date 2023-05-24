const { test, expect } = require('@playwright/test');
const { StorageStatePath, routes } = require('../utils/constants');

const mockSettlementId = 'setl_JCVHSjHRi9QHto';
const SETTLEMENT_API = '**/merchant/api/test/settlements*';
async function mockSettlementsFetch({ page, populateData }) {
  const items = [];
  if (populateData) {
    items.push({
      amount: 23886,
      created_at: 1678077015,
      entity: 'settlement',
      fees: 0,
      id: mockSettlementId,
      status: 'processed',
      tax: 0,
      utr: 'cg2mpl08cfbf3p7nghfg',
    });
  }
  await page.route(SETTLEMENT_API, (route) => {
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        status_code: 200,
        success: true,
        data: {
          entity: 'collection',
          count: items.length,
          has_more: false,
          items,
        },
      }),
    });
  });
}

test.setTimeout(1 * 60 * 1000);
// roast test settlemetsTest
test.describe.parallel('Test Settlements view @suite=payments-automation', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });
  test('should show settlements when api response has settlements @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    await mockSettlementsFetch({ page, populateData: true });
    const settlementRedirectCTA = page.getByRole('link', { name: 'View Settlements' });
    await expect(settlementRedirectCTA).toBeVisible();
    await settlementRedirectCTA.click();
    await expect(page).toHaveURL(routes.SETTLEMENTS);

    await page.waitForResponse(SETTLEMENT_API);

    const settlementRecord = await page.getByRole('button', { name: mockSettlementId });
    await expect(settlementRecord).toBeVisible();
  });

  test('should show no settlements alert when api response is empty @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    await mockSettlementsFetch({ page, populateData: false });
    const settlementRedirectCTA = page.getByRole('link', { name: 'View Settlements' });
    await expect(settlementRedirectCTA).toBeVisible();
    await settlementRedirectCTA.click();
    await expect(page).toHaveURL(routes.SETTLEMENTS);

    await page.waitForResponse(SETTLEMENT_API);

    const settlementsTable = await page.getByRole('heading', { name: 'No Settlements found!' });
    await expect(settlementsTable).toBeVisible();
  });
});
