import { routes, getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import { BASE_PATH, ERROR_MESSAGES, STATUS_TEXT } from 'apps/pos/e2e/constants';
import { queryMocks } from './mocks/handlers';
import { PaymentMethodsFieldKeyNames } from 'apps/pos/src/app/types/PaymentsAndService';

test.describe
  .parallel('POS Payment Methods & Service Selection Step @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).POS_SALES_AGENT,
  });

  test.beforeEach(async ({ page, worker }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await navigateTo(page, routes.DASHBOARD);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
  });

  test('Should render aggregator modal pricing step correctly', async ({ page, worker }) => {
    await worker.use(queryMocks.MerchantById);
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const pricingStepCard = page.getByTestId(
      'onboarding-step-card-4.paymentmethods&serviceselection',
    );
    await expect(pricingStepCard).toContainText(STATUS_TEXT.COMPLETED);
    await pricingStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/paymentMethods\/vasForm/,
    );
    await expect(page.getByText('Choose MDR Rates & Value Added Services')).toBeVisible();
    await expect(page.locator('button[aria-label="delete-file"]')).toBeDisabled();
    await expect(page.locator('button[aria-label="download-file"]')).toBeEnabled();
    await expect(page.getByTestId('mdr-edit-save')).toBeDisabled();
    await expect(page.getByTestId('vas-edit-save')).toBeDisabled();
    const saveMdrRatesBtn = page.getByRole('button', { name: 'Save & Continue' });
    await expect(saveMdrRatesBtn).toBeEnabled();
    await expect(page.locator('input[aria-label="file-upload-input"]')).toBeDisabled();
    await expect(page.getByText('sample_activation.pdf')).toBeVisible();

    const ccEmiField = page.locator(
      `input[type="checkbox"][name=${PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD}]`,
    );
    const dcEmiField = page.locator(
      `input[type="checkbox"][name=${PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD}]`,
    );
    await expect(ccEmiField).toBeChecked();
    await expect(ccEmiField).toBeDisabled();
    await expect(dcEmiField).toBeChecked();
    await expect(dcEmiField).toBeDisabled();
    await expect(page.getByTestId(PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD)).toHaveText(
      /2\.15\s*%/,
    );
    await expect(page.getByTestId(PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD)).toHaveText(
      /1\s*%/,
    );
    await expect(
      page.getByTestId(PaymentMethodsFieldKeyNames.DEBIT_CARD_RUPAY_MDR_RATE_FIELD),
    ).toHaveText(/0\.3\s*%/);
    await expect(
      page.getByTestId(
        PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_GREATER_THAN_2K_MDR_RATE_FIELD,
      ),
    ).toHaveText(/0\.9\s*%/);
    await expect(
      page.getByTestId(
        PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_LESS_THAN_2K_MDR_RATE_FIELD,
      ),
    ).toHaveText(/0\.4\s*%/);
    await expect(
      page.getByTestId(PaymentMethodsFieldKeyNames.CREDIT_CARD_MDR_RATE_FIELD),
    ).toHaveText(/1\.85\s*%/);
    await expect(
      page.getByTestId(
        PaymentMethodsFieldKeyNames.PREPAID_B2B_CORPORATE_CHANNEL_INTERNATIONAL_CARD_MDR_RATE_FIELD,
      ),
    ).toHaveText(/3\s*%/);
    await expect(page.getByTestId(PaymentMethodsFieldKeyNames.UPI_MDR_RATE_FIELD)).toHaveText(
      /0\s*%/,
    );
    await saveMdrRatesBtn.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/paymentMethods\/nachForm/,
    );
    await expect(page.getByText('Upload NACH Form')).toBeVisible();
    await expect(page.getByText('mPos (1).jpg')).toBeVisible();
    await expect(page.locator('button[aria-label="delete-file"]')).toBeDisabled();
    await expect(page.locator('button[aria-label="download-file"]')).toBeEnabled();
    await expect(page.getByText('nach png added')).toBeVisible();
    const nachCommentField = page.getByPlaceholder('Add comments here for sales');
    await expect(nachCommentField).toBeDisabled();
    await expect(nachCommentField).toHaveText('nach png added');
    const saveNachForm = page.getByRole('button', { name: 'Save & Continue' });
    await expect(saveNachForm).toBeDisabled();
    await expect(page.getByRole('button', { name: 'Skip & add later' })).toBeDisabled();
  });

  test('Should display validation error for invalid MDR rate values', async ({ page, worker }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteMerchantModularOnboardingDetailsAsSales);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const pricingStepCard = page.getByTestId(
      'onboarding-step-card-4.paymentmethods&serviceselection',
    );
    await expect(pricingStepCard).toContainText(STATUS_TEXT.PENDING);
    await pricingStepCard.click();
    const onboardingModelProceedBtn = page.getByTestId('acquisition-model-proceed');
    onboardingModelProceedBtn.click();

    const mdrEditBtn = page.getByTestId('mdr-edit-save');
    const vasEditBtn = page.getByTestId('vas-edit-save');
    await expect(mdrEditBtn).toBeEnabled();
    await expect(vasEditBtn).toBeEnabled();

    await mdrEditBtn.click();
    const debitCardInput = page.getByText('Debit Card (Rupay) %').locator('input');
    await debitCardInput.fill('-0.3');
    await mdrEditBtn.click();

    const saveMdrRatesBtn = page.getByRole('button', { name: 'Save & Continue' });
    await expect(saveMdrRatesBtn).toBeEnabled();
    await saveMdrRatesBtn.click();
    await expect(page.getByText(ERROR_MESSAGES.INVALID_DEBIT_CARD)).toBeVisible();
    await expect(page).toHaveURL('app/pos-sales/onboarding/OsZjP3fjbIskDI/paymentMethods/vasForm');
  });
});
