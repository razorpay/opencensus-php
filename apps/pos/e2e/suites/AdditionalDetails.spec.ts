import { routes, getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import {
  BASE_PATH,
  STATUS_TEXT,
  ERROR_MESSAGES,
  ADDITIONAL_DETAILS_FIELDS,
} from 'apps/pos/e2e/constants';
import { queryMocks } from '../mocks/handlers';

test.describe
  .parallel('POS Additional Details Step @flow=pos-sales-assisted @project=payments', () => {
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

  test('Should render additional details step', async ({ page, worker }) => {
    await worker.use(queryMocks.MerchantById);
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const additionalDetailsStepCard = page.getByTestId('onboarding-step-card-5.additionaldetails');
    await expect(additionalDetailsStepCard).toContainText(STATUS_TEXT.COMPLETED);
    await additionalDetailsStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/additionalDetails\/merchantAdditionalDetails/,
    );
    await expect(page.getByText('Miscellaneous Information')).toBeVisible();
    const annualTurnover = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.ANNUAL_TURNOVER,
    });
    await expect(annualTurnover).toBeDisabled();
    await expect(annualTurnover).toHaveText('Less than or Equal to ₹20L');

    const acquirerPreference = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.ACQUIRER_PREFERENCE,
    });
    await expect(acquirerPreference).toBeDisabled();
    await expect(acquirerPreference).toHaveText('Axis');

    const managerNameLabel = page
      .locator('label')
      .filter({ hasText: ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_NAME });
    await expect(managerNameLabel).toBeVisible();
    const managerNameValue = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_NAME);
    await expect(managerNameValue).toHaveValue('john');
    await expect(managerNameValue).toBeDisabled();

    const managerMobileLabel = page
      .locator('label')
      .filter({ hasText: ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_MOBILE });
    await expect(managerMobileLabel).toBeVisible();
    const managerMobileValue = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_MOBILE);
    await expect(managerMobileValue).toHaveValue('8775637732');
    await expect(managerNameValue).toBeDisabled();

    const marketingPlan = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.MARKETING_PLAN_REQUIRED,
    });
    await expect(marketingPlan).toBeDisabled();
    await expect(marketingPlan).toHaveText('Standard Plan');

    const siPartner = page.getByText(ADDITIONAL_DETAILS_FIELDS.SI_PARTNER);
    await expect(siPartner).toBeVisible();
    const siPartnerInput = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.SI_PARTNER);
    await expect(siPartnerInput).toBeDisabled();
    await expect(siPartnerInput).toHaveText('');

    const referralPartner = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.REFERRAL_PARTNER,
    });
    await expect(referralPartner).toBeDisabled();
    await expect(referralPartner).toHaveText('Select Option');

    const omcValue = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.OMC,
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
  });

  test('Should successfully complete the additional details step - Aggregator Model', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteMerchantModularOnboardingDetailsAsSales);
    await worker.use(queryMocks.completedAdditionalDetailsAggregatorModelMock);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const additionalDetailsStepCard = page.getByTestId('onboarding-step-card-5.additionaldetails');
    await expect(additionalDetailsStepCard).toContainText(STATUS_TEXT.PENDING);
    await additionalDetailsStepCard.click();

    const annualTurnover = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.ANNUAL_TURNOVER,
    });
    await annualTurnover.click();
    await page.getByRole('option', { name: 'More than ₹20L' }).click();

    const managerNameValue = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_NAME);
    await managerNameValue.fill('John Smith');

    const managerMobileValue = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_MOBILE);
    await managerMobileValue.fill('8775637732');

    const marketingPlan = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.MARKETING_PLAN_REQUIRED,
    });
    await marketingPlan.click();
    await page.getByRole('option', { name: 'Standard Plan' }).click();

    const saveBtn = page.getByRole('button', { name: 'Continue to next step' });
    await saveBtn.click();
    await expect(additionalDetailsStepCard).toContainText(STATUS_TEXT.COMPLETED);
  });

  test('Should successfully complete the additional details step - Direct Model', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteSalesOnboardingDetailsDirectModelMock);
    await worker.use(queryMocks.completedAdditionalDetailsDirectModelMock);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const additionalDetailsStepCard = page.getByTestId('onboarding-step-card-5.additionaldetails');
    await expect(additionalDetailsStepCard).toContainText(STATUS_TEXT.PENDING);
    await additionalDetailsStepCard.click();

    const annualTurnover = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.ANNUAL_TURNOVER,
    });
    await annualTurnover.click();
    await page.getByRole('option', { name: 'More than ₹20L' }).click();

    const acquirerPreference = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.ACQUIRER_PREFERENCE,
    });
    await acquirerPreference.click();
    await page.getByRole('option', { name: 'HDFC' }).click();

    const managerNameValue = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_NAME);
    await managerNameValue.fill('John Smith');

    const managerMobileValue = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_MOBILE);
    await managerMobileValue.fill('7425435342');

    const marketingPlan = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.MARKETING_PLAN_REQUIRED,
    });
    await marketingPlan.click();
    await page.getByRole('option', { name: 'Standard Plan' }).click();

    const SIPartner = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.SI_PARTNER);
    SIPartner.fill('Test');

    const referralPartner = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.REFERRAL_PARTNER,
    });
    await referralPartner.click();
    await page.getByRole('option', { name: 'ITC' }).click();

    const OMC = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.OMC,
    });
    await OMC.click();
    await page.getByRole('option', { name: 'IOCL' }).click();

    const SAPCode = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.SAP_CODE);
    await SAPCode.fill('236');

    const solutionType = page.locator('label').filter({ hasText: /^Integrated$/ });
    await solutionType.click();
    const saveBtn = page.getByRole('button', { name: 'Continue to next step' });
    await saveBtn.click();
    await expect(additionalDetailsStepCard).toContainText(STATUS_TEXT.COMPLETED);
  });

  test('Should display validation error for name of store manager, mobile number & SAP code', async ({
    page,
    worker,
  }) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.IncompleteMerchantModularOnboardingDetailsAsSales);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

    const additionalDetailsStepCard = page.getByTestId('onboarding-step-card-5.additionaldetails');
    await expect(additionalDetailsStepCard).toContainText(STATUS_TEXT.PENDING);
    await additionalDetailsStepCard.click();

    const annualTurnover = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.ANNUAL_TURNOVER,
    });
    await annualTurnover.click();
    await page.getByRole('option', { name: 'More than ₹20L' }).click();

    const managerNameValue = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_NAME);
    await managerNameValue.fill('Test@123');
    await expect(page.getByText(ERROR_MESSAGES.INVALID_NAME)).toBeVisible();

    const managerMobileValue = page.getByLabel(ADDITIONAL_DETAILS_FIELDS.STORE_MANAGER_MOBILE);
    await managerMobileValue.fill('877563773233');
    await expect(page.getByText(ERROR_MESSAGES.INVALID_NUMBER)).toBeVisible();

    const marketingPlan = page.getByRole('combobox', {
      name: ADDITIONAL_DETAILS_FIELDS.MARKETING_PLAN_REQUIRED,
    });
    await marketingPlan.click();
    await page.getByRole('option', { name: 'Standard Plan' }).click();

    const omc = page.getByRole('combobox', { name: ADDITIONAL_DETAILS_FIELDS.OMC });
    await omc.click();
    await page.getByRole('option', { name: 'IOCL' }).click();

    const sapCode = page.getByLabel('SAP Coderequired*');
    await sapCode.fill('123456');
    await sapCode.fill('');
    await expect(page.getByText(ERROR_MESSAGES.REQUIRED_SAP_CODE)).toBeVisible();

    const saveBtn = page.getByRole('button', { name: 'Continue to next step' });
    await expect(saveBtn).toBeDisabled();
  });
});
