const { test, expect } = require('@playwright/test');
const { StorageStatePath, routes } = require('../utils/constants');

async function waitAndClickViewSettlements({ page }) {
  let viewSettlementsBtn;
  try {
    viewSettlementsBtn = await page.waitForSelector(
      'button[data-blade-component="link"] >> text="View settlements"',
      {
        timeout: 5000,
      },
    );
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
test.describe('Test Settlements view when no settlments are present @suite=payments-automation @suite=payments-canary @project=payments @project=payments-roast', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });
  test('should show no settlements alert @priority=normal', async ({ page }) => {
    await page.goto(routes.SETTLEMENTS);
    await waitAndClickViewSettlements({ page });
    await expect(await page.getByText('No Settlements found!')).toBeVisible();
  });
});

test.describe('Test Settlements view when settlements are present @suite=payments-automation @suite=payments-canary @project=payments @project=payments-roast', () => {
  test.use({
    storageState: StorageStatePath.TRANSACTIONS_LOGIN_STATE,
  });
  test('should show settlements @priority=normal', async ({ page }) => {
    await page.goto(routes.SETTLEMENTS);
    await waitAndClickViewSettlements({ page });
    const settlementRecord = await page.locator('.content-wrapper table tbody tr:first-child');
    await expect(settlementRecord).toBeVisible();
    await expect(settlementRecord.getByRole('button', { name: 'Details' })).toBeVisible();
  });
});
