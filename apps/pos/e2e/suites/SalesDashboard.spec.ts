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
    await page.waitForSelector('text=Merchant Details');

    const merchants = salesOnboardedMerchantsMock.merchants;
    merchants.forEach(async (merchant) => {
      await expect(page.getByText(merchant.merchantId)).toBeVisible();
    });
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
    await expect(merchantKycStepCard).toContainText('KYC Qualified');
  });

  test('should render sales dashboard when no merchant is onboarded', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.EmptySalesOnboardedMerchantsMock);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');

    await expect(page.getByText("No data availableWe couldn't")).toBeVisible();
  });
});
