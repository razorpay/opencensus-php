import { routes, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import { STATUS_TEXT, ERROR_MESSAGES } from 'apps/pos/e2e/constants';
import { queryMocks } from '../mocks/handlers';
import path from 'path';

test.describe
  .parallel('POS Device Selection & Ordering Step @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().POS_SALES_AGENT,
  });

  test.beforeEach(async ({ page, worker }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await worker.use(queryMocks.MerchantById);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
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

  test('Should successfully complete device selection step', async ({ page, worker }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteMerchantModularOnboardingDetailsAsSales);
    await worker.use(queryMocks.CustomDeviceChargesProof);

    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const deviceStepCard = page.getByTestId('onboarding-step-card-3.deviceselection&ordering');
    await expect(deviceStepCard).toContainText(STATUS_TEXT.PENDING);
    await deviceStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/deviceSelection\/deviceSelectionCatalog/,
    );
    await expect(page.getByText('Choose Suitable Devices for your merchant')).toBeVisible();
    const addDeviceButtons = page.locator('button:has-text("Add Device")');

    for (const button of await addDeviceButtons.all()) {
      /* eslint-disable-next-line no-await-in-loop */
      await expect(button).toBeEnabled();
    }

    const androidSmartPOSDevice = page
      .getByText('Android Smart PosAdd Device')
      .locator('button:has-text("Add Device")');
    await androidSmartPOSDevice.click();
    await expect(page.locator('text=Add to Cart')).toBeVisible();

    const increaseDeviceQuantityButton = page.getByLabel('increase quantity');
    const reduceDeviceQuantityButton = page.getByLabel('reduce quantity');
    const deviceQuantity = page.getByTestId('device-quantity-text');
    await increaseDeviceQuantityButton.click();
    expect(deviceQuantity).toHaveText('2');
    await increaseDeviceQuantityButton.click();
    expect(deviceQuantity).toHaveText('3');

    const monthlyPlanDevice = page.getByTestId('monthly-plan-card-radio');
    await monthlyPlanDevice.click();
    expect(monthlyPlanDevice).toBeChecked();

    const deviceSetupFee = page.getByTestId('device_item_setup_fee_type_field-device-fee');
    await deviceSetupFee.getByText('Custom').click();
    await deviceSetupFee.locator('input[type="text"]').fill('500');

    const deviceRentalCharges = page.getByTestId(
      'device_item_rental_charges_type_field-device-fee',
    );
    await deviceRentalCharges.getByText('Custom').click();
    await deviceRentalCharges.locator('input[type="text"]').fill('500');

    await page
      .getByTestId('Collecting Rental Charges in Advance (in months)-optional-field')
      .locator('svg')
      .click();
    const rentalChargesInput = page.locator('input[name="device_item_advanced_rental_periods_field"]');
    await rentalChargesInput.fill('2');

    await worker.use(queryMocks.AddToCartMock);
    const addToCartButton = page.getByRole('button', { name: 'Add to Cart' });
    await addToCartButton.click();

    await worker.use(queryMocks.ProceedToCartMock);
    await page.getByRole('button', { name: 'Proceed to cart' }).click();
    expect(page).toHaveURL(/\/app\/pos-sales\/onboarding\/[^/]+\/deviceSelection\/deviceCart/);

    const editDeviceButton = page.getByRole('button', { name: 'Edit' });
    const confirmOrderButton = page.getByRole('button', { name: 'Confirm Order' });
    const closeButton = page.getByLabel('Close');
    expect(page.getByLabel('delete device from cart')).toBeVisible();
    expect(editDeviceButton).toBeVisible();
    expect(confirmOrderButton).toBeVisible();
    await editDeviceButton.click();
    expect(page.getByText('Device Selection', { exact: true })).toBeVisible();

    await reduceDeviceQuantityButton.click();
    expect(deviceQuantity).toHaveText('2');
    expect(deviceSetupFee.locator('input[type="text"]')).toHaveValue('500');
    expect(deviceRentalCharges.locator('input[type="text"]')).toHaveValue('500');
    expect(rentalChargesInput).toHaveValue('2');

    await page.getByRole('button', { name: 'Update Cart' }).click();
    expect(page.getByText('Device Selection', { exact: true })).not.toBeVisible();

    await page.getByRole('button', { name: 'View Details' }).click();
    expect(page.getByText('Payment Details')).toBeVisible();
    await closeButton.click();

    const changesToOrderAlert = page.getByText("Changes to your order won't");
    expect(changesToOrderAlert).toBeVisible();

    await worker.use(queryMocks.ConfirmOrderMock);
    await confirmOrderButton.click();

    expect(page.getByText(ERROR_MESSAGES.UPLOAD_CUSTOM_PRICING_MESSAGE)).toBeVisible();
    await worker.use(queryMocks.CustomDeviceChargesProof);
    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );
    await confirmOrderButton.click();

    expect(changesToOrderAlert).toBeVisible();

    await worker.use(queryMocks.ConfirmDeliveryAddressMock);
    await page.getByRole('button', { name: 'Confirm Delivery Address' }).click();

    await page.getByText('Scan and Pay').click();

    await worker.use(queryMocks.ConfirmedPaymentMock);
    await page.getByRole('button', { name: 'Check Payment Status' }).click();

    expect(page.getByRole('heading', { name: 'Order is successfully placed!' })).toBeVisible();
    await page.getByRole('button', { name: 'Continue to next step' }).click();
    await expect(deviceStepCard).toContainText(STATUS_TEXT.PAYMENT_COMPLETED);
  });

  test('Should handle pending payment scenario during device selection step', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteMerchantModularOnboardingDetailsAsSales);
    await worker.use(queryMocks.CustomDeviceChargesProof);

    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const deviceStepCard = page.getByTestId('onboarding-step-card-3.deviceselection&ordering');
    await expect(deviceStepCard).toContainText(STATUS_TEXT.PENDING);
    await deviceStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/deviceSelection\/deviceSelectionCatalog/,
    );
    await expect(page.getByText('Choose Suitable Devices for your merchant')).toBeVisible();

    const androidSmartPOSDevice = page
      .getByText('Android Smart PosAdd Device')
      .locator('button:has-text("Add Device")');
    await androidSmartPOSDevice.click();
    await expect(page.locator('text=Add to Cart')).toBeVisible();

    const increaseDeviceQuantityButton = page.getByLabel('increase quantity');
    const reduceDeviceQuantityButton = page.getByLabel('reduce quantity');
    const deviceQuantity = page.getByTestId('device-quantity-text');
    await increaseDeviceQuantityButton.click();
    expect(deviceQuantity).toHaveText('2');
    await increaseDeviceQuantityButton.click();
    expect(deviceQuantity).toHaveText('3');

    const monthlyPlanDevice = page.getByTestId('monthly-plan-card-radio');
    await monthlyPlanDevice.click();
    expect(monthlyPlanDevice).toBeChecked();

    const deviceSetupFee = page.getByTestId('device_item_setup_fee_type_field-device-fee');
    await deviceSetupFee.getByText('Custom').click();
    await deviceSetupFee.locator('input[type="text"]').fill('500');

    const deviceRentalCharges = page.getByTestId(
      'device_item_rental_charges_type_field-device-fee',
    );
    await deviceRentalCharges.getByText('Custom').click();
    await deviceRentalCharges.locator('input[type="text"]').fill('500');

    await worker.use(queryMocks.AddToCartMock);
    const addToCartButton = page.getByRole('button', { name: 'Add to Cart' });
    await addToCartButton.click();

    await worker.use(queryMocks.ProceedToCartMock);
    await page.getByRole('button', { name: 'Proceed to cart' }).click();
    expect(page).toHaveURL(/\/app\/pos-sales\/onboarding\/[^/]+\/deviceSelection\/deviceCart/);

    const editDeviceButton = page.getByRole('button', { name: 'Edit' });
    const confirmOrderButton = page.getByRole('button', { name: 'Confirm Order' });
    const closeButton = page.getByLabel('Close');
    expect(page.getByLabel('delete device from cart')).toBeVisible();
    expect(editDeviceButton).toBeVisible();
    expect(confirmOrderButton).toBeVisible();
    await editDeviceButton.click();
    expect(page.getByText('Device Selection', { exact: true })).toBeVisible();

    await reduceDeviceQuantityButton.click();
    expect(deviceQuantity).toHaveText('2');

    await page.getByRole('button', { name: 'Update Cart' }).click();
    expect(page.getByText('Device Selection', { exact: true })).not.toBeVisible();

    await page.getByRole('button', { name: 'View Details' }).click();
    expect(page.getByText('Payment Details')).toBeVisible();
    await closeButton.click();

    const changesToOrderAlert = page.getByText("Changes to your order won't");
    expect(changesToOrderAlert).toBeVisible();
    await worker.use(queryMocks.CustomDeviceChargesProof);
    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );

    await worker.use(queryMocks.ConfirmOrderMock);
    await confirmOrderButton.click();

    expect(changesToOrderAlert).toBeVisible();
    await worker.use(queryMocks.ConfirmDeliveryAddressMock);

    await page.getByRole('button', { name: 'Confirm Delivery Address' }).click();

    await page.getByText('Scan and Pay').click();

    await worker.use(queryMocks.PaymentPendingMock);
    await page.getByRole('button', { name: 'Check Payment Status' }).click();
    expect(page.getByText('Payment Pending')).toBeVisible();
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
