import {
  routes,
  test,
  expect,
  getStorageStatePath,
  playwrightEnvs,
} from '@libs/shared-qsuite/playwright';

const ELEMENT_CONFIG = {
  SETTLEMENT_BANNER: 'button[data-blade-component="link"] >> text="View settlements"',
  MERCHANT_TO_SWITCH: 'Test Account 1',
  SETTLEMENTS_TABLE: '.content-wrapper table tbody tr:first-child',
  SETTLEMENT_ID_REGEX: /^setl_.+$/,
  SETTLEMENT_DETAIL_PAGE_TITLE: 'h5[data-blade-component="heading"]',
  SETTLEMENT_TABLE_ROW_DATA: 'button > p',
};

async function waitAndClickViewSettlements({ page }) {
  try {
    const viewSettlementsBtn = await page.waitForSelector(ELEMENT_CONFIG.SETTLEMENT_BANNER);
    if (viewSettlementsBtn) await viewSettlementsBtn.click();
  } catch (error) {
    // Element not found within the specified timeout
  }
}

async function searchSettlementById(
  page,
  { settlementId, openDetails = false, statusToAssert = null },
) {
  await page.locator('input[name="id"]').fill(settlementId);
  await page.getByRole('button', { name: 'Search' }).dblclick();
  // get the first row of  table
  const settlementRecord = await page.locator(ELEMENT_CONFIG.SETTLEMENTS_TABLE);
  await expect(
    settlementRecord.getByRole('button', { name: settlementId, exact: false }),
  ).toBeVisible();
  if (statusToAssert) {
    await expect(
      settlementRecord.getByText(statusToAssert, {
        exact: false,
      }),
    ).toBeVisible();
  }
  if (openDetails) {
    const detailsCta = await settlementRecord.getByRole('button', { name: 'Details' });
    await expect(detailsCta).toBeVisible();
    // redirecting to details page
    await detailsCta.click();
    await expect(page.getByText(settlementId)).toBeVisible();
  }
  return settlementRecord;
}

async function visitSettlementsPage({ page }) {
  await page.goto(routes.SETTLEMENTS);
  const response = await page.waitForResponse('**/merchant/api/live/settlements?skip=0**');
  await waitAndClickViewSettlements({ page });
  return response;
}

// roast test settlemetsTest
test.describe('Test Settlements view when no settlments are present @flow=settlements @suite=payments-automation @suite=payments-canary @project=payments @project=payments-roast', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
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
    storageState: getStorageStatePath().SETTLEMENTS_LOGIN_STATE,
  });
  const { TEST_ENV } = playwrightEnvs;
  test('should show settlements @priority=normal', async ({ page }) => {
    await page.goto(routes.SETTLEMENTS);

    // switching merchant with settlements data
    // await switchMerchant({ page, merchantToSwitch: ELEMENT_CONFIG.MERCHANT_TO_SWITCH });

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
    await expect(page.getByText(settlementId)).toBeVisible();
  });

  test.skip('should search settlements by UTR, status and settlement id @priority=normal', async ({
    page,
  }) => {
    const response = await visitSettlementsPage({ page });

    // Extract the response JSON
    const settlementsApiData = await response.json();
    const settlementItem = settlementsApiData?.data?.items?.find((s) => s.status === 'processed');
    if (!settlementItem) {
      throw new Error('Got Invalid API response, required for this test');
    }
    const { id: settlementId, utr } = settlementItem;

    const settlementRecord = await searchSettlementById(page, { settlementId });

    await page.locator('input[name="utr"]').fill(utr);
    await page.getByRole('button', { name: 'Search' }).dblclick();
    await expect(settlementRecord.getByRole('button', { name: utr, exact: false })).toBeVisible();
    await expect(settlementRecord.getByText('Processed')).toBeVisible();
  });

  test('should reset settlements seach on click of clear @priority=normal', async ({ page }) => {
    const response = await visitSettlementsPage({ page });

    // Extract the response JSON
    const settlementsApiData = await response.json();
    const settlementItem = settlementsApiData?.data?.items?.find((s) => s.status === 'processed');
    if (!settlementItem) {
      throw new Error('Got Invalid API response, required for this test');
    }
    const { id: settlementId } = settlementItem;

    const settlementRecord = await searchSettlementById(page, { settlementId });

    await page.getByRole('button', { name: 'Clear' }).dblclick();
    await expect(
      settlementRecord.getByRole('button', { name: settlementId, exact: false }),
    ).not.toBeVisible();
  });

  test('should show settlement cycle @priority=normal', async ({ page }) => {
    await page.goto(routes.SETTLEMENTS);
    await Promise.all([
      page.waitForResponse('**/merchant/api/live/settlements?**'),
      page.waitForResponse('**/merchant/api/live/settlement/holidays**'),
    ]);
    await waitAndClickViewSettlements({ page });

    const settlementCycleCTA = await page.getByText('View Settlement Cycle');
    expect(settlementCycleCTA).toBeVisible();
    await settlementCycleCTA.click();

    await expect(page.getByRole('heading', { name: 'Settlement Cycle' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Settlement Guide' })).toBeVisible();

    const bankHolidaysCTA = await page.getByRole('button', { name: 'List of Bank Holidays' });
    expect(bankHolidaysCTA).toBeVisible();
    await bankHolidaysCTA.click();

    await expect(page.getByRole('heading', { name: 'Holidays List' })).toBeVisible();
  });

  test('should show correct details for settlement with created status  @priority=normal', async ({
    page,
  }) => {
    const response = await visitSettlementsPage({ page });

    // Extract the response JSON
    const settlementsApiData = await response.json();
    const settlementItem = settlementsApiData?.data?.items?.find((s) => s.status === 'created');
    if (!settlementItem) {
      console.log(JSON.stringify(settlementsApiData?.data?.items));
      throw new Error('Got Invalid API response, required for this test');
    }
    const { id: settlementId, status } = settlementItem;

    await searchSettlementById(page, {
      settlementId,
      openDetails: true,
      statusToAssert: status,
    });

    // assert settlment status
    await expect(page.getByText('Created', { exact: true })).toBeVisible();
    // assert UTR
    await expect(page.getByText('generated after settlement gets processed')).toBeVisible();
    // assert timeline's last stage
    await expect(page.getByText('Money to be deposited in bank account')).toBeVisible();
    await expect(page.getByText('To be deposited latest by 11:00 pm, today')).toBeVisible();
  });

  test('should show correct details for settlement with processed status  @priority=normal', async ({
    page,
  }) => {
    const response = await visitSettlementsPage({ page });

    // Extract the response JSON
    const settlementsApiData = await response.json();
    const settlementItem = settlementsApiData?.data?.items?.find((s) => s.status === 'processed');
    if (!settlementItem) {
      console.log(JSON.stringify(settlementsApiData?.data?.items));
      throw new Error('Got Invalid API response, required for this test');
    }
    const { id: settlementId, utr, status } = settlementItem;

    await searchSettlementById(page, {
      settlementId,
      openDetails: true,
      statusToAssert: status,
    });

    // assert settlment status
    await expect(page.getByText('Processed', { exact: true })).toBeVisible();
    // assert UTR
    await expect(page.getByText(`UTR number: ${utr}`)).toBeVisible();
    // assert timeline's last stage
    await expect(page.getByText('Money deposited in bank account')).toBeVisible();
  });

  test('should show gross entities for settlement @priority=normal', async ({ page }) => {
    await visitSettlementsPage({ page });

    const GrossEntitiesSettlements = {
      devstack: 'setl_EG6rVbvzzmltIR',
      canary: 'TODO',
    };

    const settlementId = GrossEntitiesSettlements[TEST_ENV];

    await searchSettlementById(page, {
      settlementId,
      openDetails: true,
    });

    const grossSettlementsTab = await page.getByTestId('settlements-gross-entities');
    await expect(grossSettlementsTab).toBeVisible();
    await expect(grossSettlementsTab.getByText('Gross Settlements', { exact: true })).toBeVisible();

    const paymentsTab = grossSettlementsTab.getByText('Payment', { exact: true });
    await expect(paymentsTab).toBeVisible();
    await expect(grossSettlementsTab.getByRole('cell', { name: 'Payment ID' })).toBeVisible();

    const adjustmentsTab = grossSettlementsTab.getByText('Adjustment', { exact: true });
    await expect(adjustmentsTab).toBeVisible();
    // switch to adjusment tab
    await adjustmentsTab.click();
    // assert rendering of adjustment tab
    await expect(grossSettlementsTab.getByRole('cell', { name: 'Adjustment ID' })).toBeVisible();
  });

  test('should show deduction entities for settlement @priority=normal', async ({ page }) => {
    await visitSettlementsPage({ page });

    const DeductionsEntitiesSettlements = {
      devstack: 'setl_EG6rVbvzzmltIR',
      canary: 'TODO',
    };

    const settlementId = DeductionsEntitiesSettlements[TEST_ENV];

    await searchSettlementById(page, {
      settlementId,
      openDetails: true,
    });

    const deductionsSettlementsTab = await page.getByTestId('settlements-deductions-entities');
    await expect(deductionsSettlementsTab).toBeVisible();
    await expect(deductionsSettlementsTab.getByText('Deductions', { exact: true })).toBeVisible();

    const refundsTab = deductionsSettlementsTab.getByText('Refund', { exact: true });
    await expect(refundsTab).toBeVisible();
    await expect(deductionsSettlementsTab.getByRole('cell', { name: 'Refund ID' })).toBeVisible();
  });
});
