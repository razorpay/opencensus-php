import { routes, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import { ERROR_MESSAGES, STATUS_TEXT, PRICING_STEP_FIELDS } from 'apps/pos/e2e/constants';
import { queryMocks } from '../mocks/handlers';
import { PaymentMethodsFieldKeyNames } from 'apps/pos/src/app/types/PaymentsAndService';
import path from 'path';

test.describe
  .parallel('POS Payment Methods & Service Selection Step @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().POS_SALES_AGENT,
  });

  test.beforeEach(async ({ page, worker }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
  });

  test('Should render aggregator modal pricing step correctly', async ({ page, worker }) => {
    await worker.use(queryMocks.MerchantById);
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const pricingStepCard = page.getByTestId(PRICING_STEP_FIELDS.PRICING_STEP_CARD);
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
    const nachCommentField = page.getByPlaceholder(PRICING_STEP_FIELDS.NACH_COMMENT_FIELD);
    await expect(nachCommentField).toBeDisabled();
    await expect(nachCommentField).toHaveText('nach png added');
    const saveNachForm = page.getByRole('button', { name: PRICING_STEP_FIELDS.SAVE_AND_CONTINUE });
    await expect(saveNachForm).toBeDisabled();
    await expect(page.getByRole('button', { name: 'Skip & add later' })).toBeDisabled();
  });

  test('should complete the pricing step successfully - Direct Model', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteSalesOnboardingDetailsDirectModelMock);
    await worker.use(queryMocks.PricingStepDirectModelSelectionMock);
    await worker.use(queryMocks.CustomRatesProofFileUploadMock);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const pricingStepCard = page.getByTestId(PRICING_STEP_FIELDS.PRICING_STEP_CARD);
    await expect(pricingStepCard).toContainText(STATUS_TEXT.PENDING);
    await pricingStepCard.click();

    await page.getByText('Direct Model').click();
    const onboardingModelProceedBtn = page.getByTestId('acquisition-model-proceed');
    onboardingModelProceedBtn.click();

    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );

    const editBtn = page.getByTestId('edit-save-btn');
    await editBtn.click();

    const ccEMIInput = page.getByText('CC EMI%').locator('input[type="text"]');
    await ccEMIInput.fill('2.15');

    const dcEMIInput = page.getByText('DC EMI%').locator('input[type="text"]');
    await dcEMIInput.fill('1');
    editBtn.click();
    await worker.use(queryMocks.NextStepNachDirectModelMock);

    const saveAndContinueBtn = page.getByRole('button', {
      name: PRICING_STEP_FIELDS.SAVE_AND_CONTINUE,
    });
    await saveAndContinueBtn.click();

    await worker.use(queryMocks.NachFileUploadMock);
    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );

    const nachCommentField = page.getByPlaceholder(PRICING_STEP_FIELDS.NACH_COMMENT_FIELD);
    await nachCommentField.fill('nach png added');

    await worker.use(queryMocks.NACHFormSubmissionDirectModelMock);
    const saveNachForm = page.getByRole('button', { name: PRICING_STEP_FIELDS.SAVE_AND_CONTINUE });
    await expect(saveNachForm).toBeEnabled();
    await expect(page.getByRole('button', { name: 'Skip & add later' })).toBeDisabled();
    await saveNachForm.click();
    await expect(pricingStepCard).toContainText(STATUS_TEXT.COMPLETED);
  });

  test('should complete the pricing step successfully - Aggregator Model', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteMerchantModularOnboardingDetailsAsSales);
    await worker.use(queryMocks.PricingStepAggregatorModelSelectionMock);
    await worker.use(queryMocks.CustomRatesProofFileUploadMock);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const pricingStepCard = page.getByTestId(PRICING_STEP_FIELDS.PRICING_STEP_CARD);
    await expect(pricingStepCard).toContainText(STATUS_TEXT.PENDING);
    await pricingStepCard.click();

    await page.getByText('Aggregator Model').click();
    const onboardingModelProceedBtn = page.getByTestId('acquisition-model-proceed');
    onboardingModelProceedBtn.click();

    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );

    const mdrEditBtn = page.getByTestId('mdr-edit-save');
    const vasEditBtn = page.getByTestId('vas-edit-save');
    await mdrEditBtn.click();
    const debitCardInput = page.getByText('Debit Card (Rupay) %').locator('input[type="text"]');
    await debitCardInput.fill('2');

    const debitCardVisaInput = page
      .getByText('Debit Card (VISA/ MasterCard/ Maestro) Greater than 2k transaction amount size%')
      .locator('input[type="text"]');
    await debitCardVisaInput.fill('2');
    await mdrEditBtn.click();
    await vasEditBtn.click();
    const ccEMIInput = page.getByText('CC EMI%').locator('input[type="text"]');
    await ccEMIInput.fill('2');

    const dcEMIInput = page.getByText('DC EMI%').locator('input[type="text"]');
    await dcEMIInput.fill('1');

    const brandEMICreditCardInput = page
      .getByText('Brand EMI Credit Card%')
      .locator('input[type="text"]');
    await brandEMICreditCardInput.fill('2');

    const brandEMIDebitCardInput = page
      .getByText('Brand EMI Debit Card%')
      .locator('input[type="text"]');
    await brandEMIDebitCardInput.fill('2');

    vasEditBtn.click();
    await worker.use(queryMocks.BrandInformationAggregatorModelMock);

    const saveAndContinueBtn = page.getByRole('button', {
      name: PRICING_STEP_FIELDS.SAVE_AND_CONTINUE,
    });
    await saveAndContinueBtn.click();

    page.getByRole('combobox', { name: PRICING_STEP_FIELDS.TYPE_OF_STORE }).click();
    await page.getByRole('option', { name: 'Multi Brand Outlet' }).click();

    page.getByTestId('brand-name-select').getByLabel(PRICING_STEP_FIELDS.BRAND_NAME).click();
    await page.getByRole('option', { name: 'Canon' }).click();

    await worker.use(queryMocks.BrandInformationValidationAggregatorModelMock);

    const saveBrandInformationBtn = page.getByRole('button', { name: 'Save' });
    await expect(saveBrandInformationBtn).toBeEnabled();
    await saveBrandInformationBtn.click();

    const saveAllBrandInformationBtn = page.getByRole('button', { name: 'Save All' });
    await saveAllBrandInformationBtn.click();

    await worker.use(queryMocks.NextStepNACHAggregatorModelMock);
    await saveAndContinueBtn.click();

    await worker.use(queryMocks.NachFileUploadMock);
    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );

    const nachCommentField = page.getByPlaceholder(PRICING_STEP_FIELDS.NACH_COMMENT_FIELD);
    await nachCommentField.fill('nach png added');

    await worker.use(queryMocks.NACHFormSubmissionDirectModelMock);
    const saveNachForm = page.getByRole('button', { name: PRICING_STEP_FIELDS.SAVE_AND_CONTINUE });
    await expect(saveNachForm).toBeEnabled();
    await expect(page.getByRole('button', { name: 'Skip & add later' })).toBeDisabled();
    await saveNachForm.click();
    await expect(pricingStepCard).toContainText(STATUS_TEXT.COMPLETED);
  });

  test('Should display validation error for invalid MDR rate values & NACH file upload', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteMerchantModularOnboardingDetailsAsSales);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const pricingStepCard = page.getByTestId(PRICING_STEP_FIELDS.PRICING_STEP_CARD);
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

    const saveMdrRatesBtn = page.getByRole('button', {
      name: PRICING_STEP_FIELDS.SAVE_AND_CONTINUE,
    });
    await expect(saveMdrRatesBtn).toBeEnabled();
    await saveMdrRatesBtn.click();
    await expect(page.getByText(ERROR_MESSAGES.INVALID_DEBIT_CARD)).toBeVisible();
    await expect(page).toHaveURL('app/pos-sales/onboarding/OsZjP3fjbIskDI/paymentMethods/vasForm');

    await mdrEditBtn.click();
    await debitCardInput.fill('0');
    await mdrEditBtn.click();

    await worker.use(queryMocks.NextStepNachDirectModelMock);
    await saveMdrRatesBtn.click();

    await worker.use(queryMocks.NachFileUploadFailure);
    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );
    await expect(page.getByText(ERROR_MESSAGES.GENERIC_ERROR_MESSAGE)).toBeVisible();

    await worker.use(queryMocks.NachFileUploadMock);
    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );
    await worker.use(queryMocks.NACHFormSubmissionDirectModelMock);
    const saveNachForm = page.getByRole('button', { name: PRICING_STEP_FIELDS.SAVE_AND_CONTINUE });
    await expect(saveNachForm).toBeEnabled();
    await saveNachForm.click();
    await expect(pricingStepCard).toContainText(STATUS_TEXT.COMPLETED);
  });
});
