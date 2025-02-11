import { routes, getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import { BASE_PATH, STATUS_TEXT, ERROR_MESSAGES } from 'apps/pos/e2e/constants';
import { queryMocks } from '../mocks/handlers';

test.describe
  .parallel('POS Device Selection & Ordering Step @flow=pos-sales-assisted @project=payments', () => {
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
  });

  test('Should render device selection step @flow=pos-sales-assisted', async ({ page, worker }) => {
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);

    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const deviceStepCard = page.getByTestId('onboarding-step-card-3.deviceselection&ordering');
    await expect(deviceStepCard).toContainText(STATUS_TEXT.COMPLETED);
    await deviceStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/deviceSelection\/deviceSelectionCatalog/,
    );
    await expect(page.getByText('Choose Suitable Devices for your merchant')).toBeVisible();
    const addDeviceButtons = page.locator('button:has-text("Add Device")');
    for (const button of await addDeviceButtons.all()) {
      /* eslint-disable-next-line no-await-in-loop */
      await expect(button).toBeDisabled();
    }
    await expect(page.getByRole('button', { name: 'Add another device' })).toBeDisabled();
    const proceedToCartBtn = page.getByRole('button', { name: 'Proceed to cart' });
    await expect(proceedToCartBtn).toBeEnabled();
    await proceedToCartBtn.click();
    await expect(page.getByText('Order Confirmation')).toBeVisible();
    await expect(page.getByText('sample_activation.pdf')).toBeVisible();
    await expect(page.locator('button[aria-label="delete-file"]')).toBeDisabled();
    await expect(page.locator('button[aria-label="download-file"]')).toBeEnabled();
    await expect(page.locator('button[aria-label="delete device from cart"]')).toBeDisabled();
    await expect(page.getByText('Android smart pos')).toBeVisible();
    await expect(page.getByText('monthly')).toBeVisible();
    await expect(page.getByText('Qty: 1')).toBeVisible();
    await expect(page.getByText('Are you sure about your order?')).toBeVisible();
    await page.getByRole('button', { name: 'View Details' }).click();

    await page.locator('button[aria-label="Close"]').click();
    await expect(page.getByRole('button', { name: 'Confirm Order' })).toBeDisabled();
  });

  test('Should render device selection step with errors', async ({ page, worker }) => {
    await worker.use(queryMocks.IncompleteMerchantModularOnboardingDetailsAsSales);

    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const deviceStepCard = page.getByTestId('onboarding-step-card-3.deviceselection&ordering');
    await expect(deviceStepCard).toContainText(STATUS_TEXT.PENDING);
    await deviceStepCard.click();

    await expect(page.getByText('Choose Suitable Devices for your merchant')).toBeVisible();
    const androidSmartPosDeviceButton = page
      .getByText('Android Smart PosAdd Device')
      .locator('button');
    await androidSmartPosDeviceButton.click();

    const setupFeeCustomButton = page
      .getByTestId('device_item_setup_fee_type_field-device-fee')
      .getByText('Custom');
    await setupFeeCustomButton.click();

    const monthlyRentalChargeCustomButton = page
      .getByTestId('device_item_rental_charges_type_field-device-fee')
      .getByText('Custom');
    await monthlyRentalChargeCustomButton.click();

    const addToCartButton = page.getByRole('button', { name: 'Add to Cart' });
    await addToCartButton.click();

    expect(
      page
        .getByTestId('device_item_setup_fee_type_field-device-fee')
        .getByText(ERROR_MESSAGES.REQUIRED_FIELD_MESSAGE),
    ).toBeVisible();
    expect(
      page
        .getByTestId('device_item_rental_charges_type_field-device-fee')
        .getByText(ERROR_MESSAGES.REQUIRED_FIELD_MESSAGE),
    ).toBeVisible();
  });
});
