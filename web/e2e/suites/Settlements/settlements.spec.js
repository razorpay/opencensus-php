const { test, expect } = require('@playwright/test');
const { StorageStatePath, routes } = require('../../utils/constants');
const { switchMerchant } = require('../../utils/common');

const ELEMENT_CONFIG = {
  SETTLEMENT_BANNER: 'button[data-blade-component="link"] >> text="View settlements"',
  MERCHANT_TO_SWITCH: 'Test Account 1',
  SETTLEMENTS_TABLE: '.content-wrapper table tbody tr:first-child',
  SETTLEMENT_ID_REGEX: /^setl_.+$/,
  SETTLEMENT_DETAIL_PAGE_TITLE: 'h5[data-blade-component="heading"]',
  SETTLEMENT_TABLE_ROW_DATA: 'button > p',
};

async function waitAndClickViewSettlements({ page }) {
  let viewSettlementsBtn;
  try {
    viewSettlementsBtn = await page.waitForSelector(ELEMENT_CONFIG.SETTLEMENT_BANNER, {
      timeout: 5000,
    });
  } catch (err) {
    // supress error thrown if element is not found
  }
  if (viewSettlementsBtn) {
    await viewSettlementsBtn.click();
  } else {
    console.log('view settlments button not found');
  }
}

// roast test settlemetsTest
test.describe('Test Settlements view when no settlments are present @flow=settlements @suite=payments-automation @suite=payments-canary @project=payments @project=payments-roast', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });
  test('should show no settlements alert @priority=normal', async ({ page }) => {
    await page.goto(routes.SETTLEMENTS);

    // settlements banner proceed click
    await waitAndClickViewSettlements({ page });
    await expect(await page.getByText('No Settlements found!')).toBeVisible();
  });
});

test.describe('Test Settlements view when settlements are present @flow=settlements @suite=payments-automation @suite=payments-canary @project=payments @project=payments-roast', () => {
  test.use({
    storageState: StorageStatePath.SETTLEMENTS_LOGIN_STATE,
  });
  test('should show settlements @priority=normal', async ({ page }) => {
    await page.goto(routes.SETTLEMENTS);

    // switching merchant with settlements data
    await switchMerchant({ page, merchantToSwitch: ELEMENT_CONFIG.MERCHANT_TO_SWITCH });

    // settlements banner proceed click
    await waitAndClickViewSettlements({ page });

    const settlementRecord = await page.locator(ELEMENT_CONFIG.SETTLEMENTS_TABLE);
    await expect(settlementRecord).toBeVisible();

    // getting id of the settlement
    const settlementIdColumn = await settlementRecord.getByRole('button', {
      name: ELEMENT_CONFIG.SETTLEMENT_ID_REGEX,
    });

    const settlementId = await settlementIdColumn.textContent();

    // check view details action of settlements table
    const detailsCta = await settlementRecord.getByRole('button', { name: 'Details' });
    await expect(detailsCta).toBeVisible();

    // redirecting to details page
    await detailsCta.click();

    // verifying details page with settlement id
    await expect(
      await page.locator(ELEMENT_CONFIG.SETTLEMENT_DETAIL_PAGE_TITLE, {
        hasText: `Settlement details - ${settlementId}`,
      }),
    ).toBeVisible();
  });
});
