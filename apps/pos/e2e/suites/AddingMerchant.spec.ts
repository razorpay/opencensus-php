import { routes, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import { SME_DASHBOARD_URL } from 'apps/pos/e2e/constants';
import { queryMocks } from '../mocks/handlers';

test.describe
  .parallel('POS Adding a new Merchant @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().POS_SALES_AGENT,
  });

  test('should successfully add a new merchant', async ({ page, worker }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);
    await worker.use(queryMocks.SendOTP);
    await worker.use(queryMocks.VerifyOTP);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');

    await page.getByRole('button', { name: 'Add Merchant' }).click();
    const addMerchantStepCard = page.getByTestId('onboarding-step-card-1.addinganewmerchant');
    await expect(addMerchantStepCard).toContainText('Pending');
    await expect(addMerchantStepCard).toBeEnabled();
    await addMerchantStepCard.click();

    const mobileNumberInput = page.getByLabel("Let's get merchant's mobile");
    await mobileNumberInput.fill('1234567890');
    await page.getByRole('button', { name: 'Verify' }).click();

    const otp = ['0', '0', '0', '0', '0', '7'];
    const otpInputs = page.locator('input[type="text"]');

    for (let i = 0; i < otp.length; i++) {
      otpInputs.nth(i).fill(otp[i]);
    }
    const submitOTPBtn = page.getByRole('button', { name: 'Submit OTP' });
    await expect(submitOTPBtn).toBeEnabled();
    await submitOTPBtn.click();
    await expect(page.getByText('Redirecting you to the onboarding journey...')).toBeVisible();
    await page.waitForURL(SME_DASHBOARD_URL);
    expect(page.url()).toContain(SME_DASHBOARD_URL);
  });
});
