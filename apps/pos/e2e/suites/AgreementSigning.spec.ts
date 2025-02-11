import { routes, getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import { BASE_PATH, STATUS_TEXT } from 'apps/pos/e2e/constants';
import { queryMocks } from '../mocks/handlers';

test.describe
  .parallel('POS Agreement Signing Step @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).POS_SALES_AGENT,
  });

  test.beforeEach(async ({ page, worker }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await worker.use(queryMocks.MerchantById);
    await navigateTo(page, routes.DASHBOARD);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();
  });

  test('Should render agreement signing step', async ({ page, worker }) => {
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);

    const agreementSigningStepCard = page.getByTestId('onboarding-step-card-6.agreementsigning');
    await expect(agreementSigningStepCard).toContainText(STATUS_TEXT.COMPLETED);
    await agreementSigningStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/agreementSigning\/agreementMode/,
    );
    await expect(page.getByText('KYC details submitted successfully!')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Back to dashboard' })).toBeEnabled();
    await page.getByRole('button', { name: 'Close' }).click();
    await expect(page.getByText('Choose mode of agreement signing')).toBeVisible();
    const onlineMode = page.locator('input[type="radio"][value="online"]');
    await expect(onlineMode).not.toBeChecked();
    await expect(onlineMode).toBeDisabled();

    const offlineMode = page.locator('input[type="radio"][value="offline"]');
    await expect(offlineMode).toBeChecked();
    await expect(offlineMode).toBeDisabled();
    await expect(page.locator('input[aria-label="file-upload-input"]')).toBeDisabled();
    await expect(page.getByText('sample_activation.pdf')).toBeVisible();
    await expect(page.locator('button[aria-label="delete-file"]')).toBeDisabled();
    await expect(page.locator('button[aria-label="download-file"]')).toBeEnabled();
  });
});
