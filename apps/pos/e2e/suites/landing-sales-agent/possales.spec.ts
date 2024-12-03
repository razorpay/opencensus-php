import { routes, getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
import { test, expect } from '../../utils/test';
import { waitForSalesAssistedScreenToLoad } from '../../utils';
import { BASE_PATH } from '../../constants';
import { queryMocks } from './mocks/handlers';
import { salesOnboardedMerchantsMock } from './mocks/fixtures';
import { PaymentMethodsFieldKeyNames } from 'apps/pos/src/app/types/PaymentsAndService';

test.describe.parallel('POS activation status @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).POS_SALES_AGENT,
  });
  test('should render sales dashboard view if logged in as sales agent @flow=pos-sales-assisted', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await worker.use(queryMocks.MerchantById);
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);
    await navigateTo(page, routes.DASHBOARD);
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
    // DEVICE SELECTION STEP
    const deviceStepCard = page.getByTestId('onboarding-step-card-3.deviceselection&ordering');
    await expect(deviceStepCard).toContainText('Payment Completed');
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
    await page.getByLabel('header-back-btn').click();
    await page.getByLabel('header-back-btn').click();

    //PRICING STEP
    const pricingStepCard = page.getByTestId(
      'onboarding-step-card-4.paymentmethods&serviceselection',
    );
    await expect(pricingStepCard).toContainText('Completed');
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
    await page.getByLabel('header-back-btn').click();
    await page.getByLabel('header-back-btn').click();

    //ADDITIONAL DETAILS STEP
    const additionalDetailsStepCard = page.getByTestId('onboarding-step-card-5.additionaldetails');
    await expect(additionalDetailsStepCard).toContainText('Completed');
    await additionalDetailsStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/additionalDetails\/merchantAdditionalDetails/,
    );
    await expect(page.getByText('Miscellaneous Information')).toBeVisible();
    const annualTurnover = page.getByRole('combobox', { name: 'Annual Turnover required *' });
    await expect(annualTurnover).toBeDisabled();
    await expect(annualTurnover).toHaveText('Less than or Equal to ₹20L');

    const acquirerPreference = page.getByRole('combobox', {
      name: 'Acquirer Preference required *',
    });
    await expect(acquirerPreference).toBeDisabled();
    await expect(acquirerPreference).toHaveText('Axis');

    const managerNameLabel = page.locator('label').filter({ hasText: 'Name of Store Manager/' });
    await expect(managerNameLabel).toBeVisible();
    const managerNameValue = page.getByLabel('Name of Store Manager/');
    await expect(managerNameValue).toHaveValue('john');

    const managerMobileLabel = page.locator('label').filter({ hasText: 'Mobile number of Store' });
    await expect(managerMobileLabel).toBeVisible();
    const managerMobileValue = page.getByLabel('Mobile number of Store');
    await expect(managerMobileValue).toHaveValue('8775637732');

    const marketingPlan = page.getByRole('combobox', {
      name: 'Marketing plan required *',
    });
    await expect(marketingPlan).toBeDisabled();
    await expect(marketingPlan).toHaveText('Standard Plan');

    const siPartner = page.getByText('SI Partner');
    await expect(siPartner).toBeVisible();
    const siPartnerInput = page.getByLabel('SI Partner');
    await expect(siPartnerInput).toBeDisabled();
    await expect(siPartnerInput).toHaveText('');

    const referralPartner = page.getByRole('combobox', {
      name: 'Referral Partner',
    });
    await expect(referralPartner).toBeDisabled();
    await expect(referralPartner).toHaveText('Select Option');

    const omcValue = page.getByRole('combobox', {
      name: 'OMC',
    });
    await expect(omcValue).toBeDisabled();
    await expect(omcValue).toHaveText('Select Option');

    const solutionTypeLabel = page.getByLabel('Solution Type');
    await expect(solutionTypeLabel).toBeVisible();
    const nonIntegrated = page.getByText('Non-Integrated');
    await expect(nonIntegrated).toBeVisible();
    const integrated = page.getByText('Integrated', { exact: true });
    await expect(integrated).toBeVisible();

    const nonIntegratedSolutionType = page.locator('input[type="radio"][value="non_integrated"]');
    await expect(nonIntegratedSolutionType).not.toBeChecked();
    await expect(nonIntegratedSolutionType).toBeDisabled();

    const integratedSolutionType = page.locator('input[type="radio"][value="integrated"]');
    await expect(integratedSolutionType).toBeChecked();
    await expect(integratedSolutionType).toBeDisabled();

    const saveBtn = page.getByRole('button', { name: 'Continue to next step' });
    await expect(saveBtn).toBeDisabled();
    await page.getByLabel('header-back-btn').click();

    //AGREEMENT SIGNING STEP
    const agreementSigningStepCard = page.getByTestId('onboarding-step-card-6.agreementsigning');
    await expect(agreementSigningStepCard).toContainText('Completed');
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
