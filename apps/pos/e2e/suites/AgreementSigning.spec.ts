import { routes, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { test, expect } from 'apps/pos/e2e/utils/test';
import { waitForSalesAssistedScreenToLoad } from 'apps/pos/e2e/utils';
import { STATUS_TEXT } from 'apps/pos/e2e/constants';
import { queryMocks } from '../mocks/handlers';
import path from 'path';

test.describe
  .parallel('POS Agreement Signing Step @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    // @ts-expect-error
    storageState: getStorageStatePath().POS_SALES_AGENT,
  });

  test.beforeEach(async ({ page, worker }: any) => {
    await worker.use(queryMocks.SalesOnboardedMerchants);
    await page.goto(routes.DASHBOARD);
    await expect(page).toHaveTitle(/Razorpay Dashboard/);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
    await page.waitForSelector('text=Merchant Details');
  });

  test('Should render agreement signing step', async ({ page, worker }: any) => {
    await worker.use(queryMocks.MerchantById);
    await worker.use(queryMocks.MerchantModularOnboardingDetailsAsSales);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();

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

  test('Agent should be able to click on mode of aggrement sigining based on merchant preference ( online / offline)', async ({
    page,
    worker,
  }: any) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.AgreementPendingState);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();
    const agreementSigningStepCard = page.getByTestId('onboarding-step-card-6.agreementsigning');
    await expect(agreementSigningStepCard).toContainText(STATUS_TEXT.PENDING);
    await agreementSigningStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/agreementSigning\/agreementMode/,
    );
    await expect(page.getByText('Choose mode of agreement signing')).toBeVisible();
    const onlineMode = page.locator('input[type="radio"][value="online"]');
    await expect(onlineMode).toBeChecked();
    await expect(onlineMode).toBeEnabled();
    const ctaLabelOnline = await page.getByLabel('send-link-btn');
    await expect(ctaLabelOnline).toContainText('Generate Link');
    const offlineMode = page.locator('input[type="radio"][value="offline"]');
    await expect(offlineMode).not.toBeChecked();
    await page.locator('label').filter({ hasText: 'Offline' }).click();
    const ctaLabelOffline = await page.getByLabel('send-link-btn');
    await expect(ctaLabelOffline).toContainText('Submit Merchant Details');
    await expect(offlineMode).toBeEnabled();
  });

  test('Agent on choosing the online payment and clicking on generate link button', async ({
    page,
    worker,
  }: any) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.AgreementPendingState);
    await worker.use(queryMocks.AgreementOnlineModeMutation);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();
    const agreementSigningStepCard = page.getByTestId('onboarding-step-card-6.agreementsigning');
    await expect(agreementSigningStepCard).toContainText(STATUS_TEXT.PENDING);
    await agreementSigningStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/agreementSigning\/agreementMode/,
    );
    // check radio button is checked and enabled
    await expect(page.getByText('Choose mode of agreement signing')).toBeVisible();
    const onlineMode = page.locator('input[type="radio"][value="online"]');
    await expect(onlineMode).toBeChecked();
    await expect(onlineMode).toBeEnabled();
    // check cta label is 'Generate Link'
    const ctaLabelOnline = await page.getByLabel('send-link-btn');
    await expect(ctaLabelOnline).toContainText('Generate Link');
    await ctaLabelOnline.click();
    // check two badges are visible
    const matchingElements = await page
      .locator('div[data-blade-component="badge"]')
      .filter({ hasText: /Action Pending|Agreement Sent/ })
      .all();
    expect(matchingElements.length).toBe(2);
    // check copy link functionality
    const copyButton = await page.getByRole('button', { name: 'Copy link' });
    await expect(copyButton).toBeVisible();
    await copyButton.click();
    const toastLinkCopy = await page.getByText('Link copied successfully');
    await expect(toastLinkCopy).toBeVisible();
    // check resend link functionality
    const resendCTA = await page.getByLabel('send-link-btn');
    await expect(resendCTA).toContainText('Re-send Link');
    await resendCTA.click();
    const toastResentLink = await page.getByText('Agreement re-sent successfully');
    await expect(toastResentLink).toBeVisible();
    // check state of agreement signing step card after online link generation is pending
    await page.getByLabel('header-back-btn').click();
    const agreementSigningStepCardAfterOnlineLinkGen = page.getByTestId(
      'onboarding-step-card-6.agreementsigning',
    );
    await expect(agreementSigningStepCardAfterOnlineLinkGen).toContainText(STATUS_TEXT.PENDING);
  });

  test('Sigining status should be in completed once merchant complete Agreement', async ({
    page,
    worker,
  }: any) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.AgreementOnlineCompleted);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();
    const agreementSigningStepCard = page.getByTestId('onboarding-step-card-6.agreementsigning');
    await expect(agreementSigningStepCard).toContainText(STATUS_TEXT.COMPLETED);
    await agreementSigningStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/agreementSigning\/agreementMode/,
    );
    // check radio button is checked and disabled
    await expect(page.getByText('Choose mode of agreement signing')).toBeVisible();
    const onlineMode = page.locator('input[type="radio"][value="online"]');
    await expect(onlineMode).toBeChecked();
    await expect(onlineMode).toBeDisabled();
    // check two badges are visible
    const matchingElements = await page
      .locator('div[data-blade-component="badge"]')
      .filter({ hasText: /Successful|Agreement Sent/ })
      .all();
    expect(matchingElements.length).toBe(2);
    await expect(page.getByText('KYC details submitted')).toBeVisible();
  });

  test('should be able to upload T&C pricing aggrement signed form on choosing the offline payment', async ({
    page,
    worker,
  }: any) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.AgreementPendingState);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();
    const agreementSigningStepCard = page.getByTestId('onboarding-step-card-6.agreementsigning');
    await expect(agreementSigningStepCard).toContainText(STATUS_TEXT.PENDING);
    await agreementSigningStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/agreementSigning\/agreementMode/,
    );
    const offlineMode = page.locator('input[type="radio"][value="offline"]');
    await expect(offlineMode).not.toBeChecked();
    await page.locator('label').filter({ hasText: 'Offline' }).click();
    await expect(page.getByText('Upload TnC & Pricing Agreement')).toBeVisible();
    await worker.use(queryMocks.AgreementFileUploadMock);
    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );
    await page.getByText('test-document.png');
    await expect(page.locator('button[aria-label="download-file"]')).toBeEnabled();
    await expect(page.locator('button[aria-label="delete-file"]')).toBeEnabled();
    await worker.use(queryMocks.AgreementOfflineCompleteState);
    const submitCTA = await page.getByLabel('send-link-btn');
    await expect(submitCTA).toContainText('Submit Merchant Details');
    await submitCTA.click();
    const toastSubmitMerchantDetails = await page.getByText('KYC details submitted');
    await expect(toastSubmitMerchantDetails).toBeVisible();
    await expect(page.getByText('KYC details submitted')).toBeVisible();
    await page.getByLabel('Close').click();
    await page.getByLabel('header-back-btn').click();
    await expect(agreementSigningStepCard).toContainText(STATUS_TEXT.COMPLETED);
  });

  test('On unsuccessful upload, state of rental aggrement step should be marked with pending status', async ({
    page,
    worker,
  }: any) => {
    await worker.use(queryMocks.MerchantByIdStatus);
    await worker.use(queryMocks.AgreementPendingState);
    const detailsLink = page.getByTestId('OsZjP3fjbIskDI');
    await detailsLink.click();
    const agreementSigningStepCard = page.getByTestId('onboarding-step-card-6.agreementsigning');
    await expect(agreementSigningStepCard).toContainText(STATUS_TEXT.PENDING);
    await agreementSigningStepCard.click();
    await expect(page).toHaveURL(
      /\/app\/pos-sales\/onboarding\/OsZjP3fjbIskDI\/agreementSigning\/agreementMode/,
    );
    const offlineMode = page.locator('input[type="radio"][value="offline"]');
    await expect(offlineMode).not.toBeChecked();
    await page.locator('label').filter({ hasText: 'Offline' }).click();
    await expect(page.getByText('Upload TnC & Pricing Agreement')).toBeVisible();
    await worker.use(queryMocks.AgreementFileUploadFailure);
    await page.setInputFiles(
      'input[type="file"]',
      path.resolve(__dirname, '../files/test-document.png'),
    );
    await page.pause();
    await expect(page.getByText('Some error occurred while uploading file!')).toBeVisible();
    const submitCTA = await page.getByLabel('send-link-btn');
    await expect(submitCTA).toContainText('Submit Merchant Details');
    await submitCTA.click();
    await expect(page.getByText('Please upload a file')).toBeVisible();
    await page.getByLabel('header-back-btn').click();
    await expect(agreementSigningStepCard).toContainText(STATUS_TEXT.PENDING);
  });
});
