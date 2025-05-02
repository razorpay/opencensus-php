import { routes, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import { queryMocks } from '../mocks/handlers';
import { salesOnboardedMerchantsMock } from '../mocks/fixtures';

test.describe.parallel('POS Sales Dashboard @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().POS_SALES_AGENT,
  });
  test('should render sales dashboard view if logged in as sales agent @flow=pos-sales-assisted', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.getByRole('heading', { name: 'Merchant Details' });

    const merchants = salesOnboardedMerchantsMock.merchants;
    // Wait for all merchant ID checks to complete before proceeding
    await Promise.all(
      merchants.map(async (merchant) => {
        await expect(page.getByText(merchant.merchantId)).toBeVisible();
      }),
    );

    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();
    await expect(page.getByText('Adding a New Merchant')).toBeVisible();
    await expect(page.getByText('Merchant KYC')).toBeVisible();
    await expect(page.getByText('Device Selection & Ordering')).toBeVisible();
    await expect(page.getByText('Payment Methods & Service Selection')).toBeVisible();
    await expect(page.getByText('Additional Details')).toBeVisible();
    await expect(page.getByText('Agreement Signing')).toBeVisible();

    //MERCHANT KYC STEP
    const merchantKycStepCard = page.getByTestId('onboarding-step-card-2.merchantkyc');
    await expect(merchantKycStepCard).toContainText('Activated');
  });

  test('should render activation filter chips', async ({ page, worker }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await page.goto(routes.DASHBOARD);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
    const filterChips = [
      'Activated - 1',
      'Pending - 10',
      'Rejected - 2',
      'KYC Qualified - 4',
      'KYC Needs Clarification - 3',
      'Pricing Needs Clarification - 7',
      'Under Review - 6',
    ];
    filterChips.forEach(async (statusChip) => {
      await expect(page.getByText(statusChip)).toBeVisible();
    });
  });

  test('should filter table data based on activated status chip', async ({ page, worker }) => {
    await worker.use(queryMocks.ActivatedSalesOnboardedMerchants);
    await page.goto(routes.DASHBOARD);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
    await page.getByTestId('activated').click();
    const merchant1 = page.getByTestId('OsZjP3fjbIskDI');
    const merchant2 = page.getByTestId('OsZj7mow3GcMTb');
    await expect(merchant1).toBeVisible();
    await expect(merchant2).toBeVisible();
  });

  test('should filter table data based on pricing NC status chip', async ({ page, worker }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await page.goto(routes.DASHBOARD);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
    await page.getByTestId('pricingNeedsClarification').click();
    const merchant = page.getByTestId('OsZj7mow3GcMTb');
    await expect(merchant).toBeVisible();
  });

  test('should filter table data based on date filter', async ({ page, worker }) => {
    await worker.use(queryMocks.PastSevenDaysMerchants);
    await page.goto(routes.DASHBOARD);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
    const startDateInput = await page.getByRole('combobox', { name: /Start Date/i });
    expect(startDateInput).toBeVisible();
    const endDateInput = await page.getByRole('combobox', { name: /End Date/i });
    expect(endDateInput).toBeVisible();
    await startDateInput.click();
    const sevenDaysPresetChip = await page.getByText('Past 7 days');
    await sevenDaysPresetChip.click();
    await page.getByRole('button', { name: /Apply/i }).click();
    const merchant = page.getByTestId('OsZjP3fjbIsxyz');
    await expect(merchant).toBeVisible();
  });

  test('should clear all filters when clicked on Clear Filter button', async ({ page, worker }) => {
    await worker.use(queryMocks.PastSevenDaysMerchants);
    await page.goto(routes.DASHBOARD);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
    const startDateInput = await page.getByRole('combobox', { name: /Start Date/i });
    expect(startDateInput).toBeVisible();
    const endDateInput = await page.getByRole('combobox', { name: /End Date/i });
    expect(endDateInput).toBeVisible();
    await startDateInput.click();
    const sevenDaysPresetChip = await page.getByText('Past 7 days');
    await sevenDaysPresetChip.click();
    await page.getByRole('button', { name: /Apply/i }).click();
    const merchant = page.getByTestId('OsZjP3fjbIsxyz');
    await expect(merchant).toBeVisible();
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await page.getByRole('button', { name: /Clear Filters/i }).click();
    const merchant1 = page.getByTestId('OsZjP3fjbIskDI');
    expect(merchant1).toBeVisible();
  });

  test('should render sales dashboard when no merchant is onboarded', async ({ page, worker }) => {
    await worker.use(queryMocks.EmptySalesOnboardedMerchantsMock);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.getByRole('heading', { name: 'Merchant Details' });
    await expect(page.getByText("No data availableWe couldn't")).toBeVisible();
  });

  test('should render the search bar and allow input (mobile view)', async ({ page, worker }) => {
    await worker.use(queryMocks.EmptySalesOnboardedMerchantsMock);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.getByRole('heading', { name: 'Merchant Details' });

    await page.getByPlaceholder('Search in payments').click();
    await page.setViewportSize({ width: 375, height: 812 });

    await expect(page.getByRole('img', { name: 'Empty search' })).toBeVisible();
    const searchInput = page.getByPlaceholder('Search');
    await searchInput.fill('Test');
    await expect(page.getByRole('img', { name: 'No results found' })).toBeVisible();

    await worker.use(queryMocks.SalesOnboardedMerchants);
    await searchInput.fill('Razorpay');
    const searchItem = page.getByText('Razorpay pvtOsZjEdjUnxCuyu').nth(1);
    await searchItem.click();

    await expect(page.getByText('Onboarding Details')).toBeVisible();
    const completeNowButton = page.getByRole('button', { name: 'Complete now' });
    await completeNowButton.click();

    await expect(page).toHaveURL(/\/app\/pos-sales\/onboarding\/OsZjEdjUnxCuyu$/);
  });

  test('should render the search bar and allow input (desktop view)', async ({ page, worker }) => {
    await worker.use(queryMocks.EmptySalesOnboardedMerchantsMock);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.getByRole('heading', { name: 'Merchant Details' });

    await page.getByPlaceholder('Search in payments').click();
    await expect(page.getByRole('img', { name: 'Empty search' })).toBeVisible();
    const searchInput = page.getByTestId('modal-wrapper').getByPlaceholder('Search');
    await searchInput.fill('Test');
    await expect(page.getByRole('img', { name: 'No results found' })).toBeVisible();

    await worker.use(queryMocks.SalesOnboardedMerchants);
    await searchInput.fill('Razorpay');
    const searchItem = page.getByText('Razorpay pvtOsZjEdjUnxCuyu');
    await searchItem.click();

    await expect(page.getByText('Onboarding Details')).toBeVisible();
    const completeNowButton = page.getByRole('button', { name: 'Complete now' });
    await completeNowButton.click();

    await expect(page).toHaveURL(/\/app\/pos-sales\/onboarding\/OsZjEdjUnxCuyu$/);
  });
});
