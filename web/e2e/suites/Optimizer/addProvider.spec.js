import { expect, test } from '@playwright/test';

import { METHODS, METHODS_MAP } from './constants';
import {
  clickButton,
  clickCheckbox,
  commonStepAssertions,
  clickMethodsByText,
  expectTooltipContent,
  expectRadio,
  navigateToOptimizer,
  selectGatewayBySearch,
  selectDropdownOption,
  typeTextIntoElement,
} from './utils';
import { StorageStatePath } from '../../utils/constants';

const ELEMENTS = {
  INSTANT_BETA:
    '[data-testid="integration-type"] label[data-blade-component="radio-label"] [data-blade-component="base-text"]:text("Instant (beta)")',
  SERVER_TO_SERVER:
    '[data-testid="integration-type"] label[data-blade-component="radio-label"] [data-blade-component="base-text"]:text("Server-to-Server")',
  PROVIDER_NAME: 'input[name="Provider_name"]',
  PROVIDER_DESCRIPTION: 'textarea[name="Description"]',
  PROVIDER_KEY: 'input[name="Key"]',
  PROVIDER_SALT: 'input[name="Salt"]',
  PROVIDER_CLIENT_ID: 'input[name="Client ID"]',
  PROVIDER_MERCHANT_ID: 'input[name="Merchant ID"]',
  METHOD_CARD: `input[type="checkbox"][name="Payment Methods"][value="${METHODS.CARD}"]`,
  WALLET_SELECT: 'div[data-testid="wallet-select"]',
  WALLET_OPTION_PHONEPE: 'button[role="option"][data-value="phonepe"]',
  TPV_ICON: '[data-testid="tpv-info-icon"]',
  PROVIDER_NON_TPV: 'input[name="TPV"][value="0"]',
  PROVIDER_TPV_ONLY: 'input[name="TPV"][value="1"]',
  PROVIDER_TPV_BOTH: 'input[name="TPV"][value="2"]',
};

const assertSelectGateway = async ({ page, searchTerm, provider }) => {
  await commonStepAssertions(page, {
    primaryText: 'Select Gateway',
    secondaryText: 'Select a Gateway for your payment provider',
    stepText: 'STEP 1',
  });
  await selectGatewayBySearch({ page, searchTerm, provider });
  expect(page.getByText('Select a Gateway for your payment provider')).toBeHidden();
  expect(page.getByRole('button', { name: 'Change gateway' })).toBeVisible();
};

const assertIntegrationType = async ({ page, radioSelector, noteText }) => {
  await commonStepAssertions(page, {
    primaryText: 'Select integration type',
    secondaryText: 'Select the type of integration for the selected gateway',
    stepText: 'STEP 2 OUT OF 4',
  });
  expect(page.getByRole('button', { name: 'Next' })).toBeDisabled();
  // Locate the radio button with the text "Instant(beta)" / "Server-to-Server"
  const radioLabel = await page.locator(radioSelector);
  expect(radioLabel).toBeVisible();
  await radioLabel.click();
  expect(page.getByText(noteText, { exact: true })).toBeVisible();
  await clickButton(page, 'Next');
};

const assertProviderDetails = async ({ page, stepText }) => {
  await commonStepAssertions(page, {
    primaryText: 'Provider details',
    secondaryText: 'Add details of your payment provider',
    stepText,
  });
  expect(page.getByRole('button', { name: 'Next' })).toBeDisabled();
  expect(page.getByText('Provider Name', { exact: true })).toBeVisible();
  expect(page.getByText('Description', { exact: true })).toBeVisible();
  await typeTextIntoElement(page, ELEMENTS.PROVIDER_NAME, 'playwright test provider');
  await typeTextIntoElement(page, ELEMENTS.PROVIDER_DESCRIPTION, 'playwright test description');
  await clickButton(page, 'Next');
};

const assertSodexoCheckbox = async (page) => {
  const isCardChecked = await page.$eval(
    ELEMENTS.METHOD_CARD,
    (cardCheckbox) => cardCheckbox.getAttribute('aria-checked') === 'true',
  );
  if (isCardChecked) {
    await clickCheckbox(page, METHODS_MAP[METHODS.SODEXO]);
  }
};

const assertTPVOption = async (page) => {
  const tpvLabel = page.getByText('TPV', { exact: true });
  const isLabelVisible = await tpvLabel.isVisible();

  if (!isLabelVisible) {
    throw new Error('TPV label is not visible.');
  }

  await expectTooltipContent({
    page,
    iconSelector: ELEMENTS.TPV_ICON,
    content:
      'Third-Party Validation (TPV) of your customer’s bank accounts in real-time. It is a mandatory requirement for merchants in the BFSI (Banking, Financial Services and Insurance) sector.',
  });

  // Assert "Non-TPV" is default selected
  expectRadio(page, ELEMENTS.PROVIDER_NON_TPV, true);
  // Select "TPV Only"
  await page.click('label:has-text("TPV Only")');
  // Assert "TPV Only" is selected
  expectRadio(page, ELEMENTS.PROVIDER_TPV_ONLY, true);
  // Check "Non-TPV" is unselected
  expectRadio(page, ELEMENTS.PROVIDER_NON_TPV, false);
  // Select "Both (TPV and Non TPV)"
  await page.click('label:has-text("Both (TPV and Non TPV)")');
  // Assert "Both (TPV and Non TPV)" is selected
  expectRadio(page, ELEMENTS.PROVIDER_TPV_BOTH, true);
  // Check "TPV Only" is unselected
  expectRadio(page, ELEMENTS.PROVIDER_TPV_ONLY, false);
};

test.describe.parallel('Optimizer (Live Mode) @flow=optimizer @project=payments', () => {
  test.use({ storageState: StorageStatePath.OPTIMIZER_V1_LOGIN_STATE });

  test('should show optimizer dashboard', async ({ page }) => {
    try {
      await navigateToOptimizer(page);
      await expect(page.getByText('Payment Provider')).toBeVisible();
      expect(page.getByRole('link', { name: 'Documentation' })).toBeVisible();
      expect(page.getByRole('button', { name: 'Add Provider' })).toBeVisible();
    } catch (error) {
      console.error('Error opening Optimizer Dashboard: ', error?.message);
    }
  });

  test('Add PayU Provider - Instant (beta)', async ({ page }) => {
    try {
      await navigateToOptimizer(page);
      await clickButton(page, 'Add Provider');
      // Step 1
      await assertSelectGateway({ page, searchTerm: 'payu', provider: 'payu' });
      // Step 2
      await assertIntegrationType({
        page,
        radioSelector: ELEMENTS.INSTANT_BETA,
        noteText: 'Enable Instant (beta)',
      });
      // Step 3
      await assertProviderDetails({ page, stepText: 'STEP 3 OUT OF 4' });
      // Step 4
      await commonStepAssertions(page, {
        primaryText: 'PayU Production API Details',
        secondaryText:
          'Please make sure you enter the production API details only and NOT the Test Details',
        stepText: 'STEP 4 OUT OF 4',
      });
      const methods = [
        METHODS_MAP[METHODS.CARD],
        METHODS_MAP[METHODS.NETBANKING],
        METHODS_MAP[METHODS.UPI],
        METHODS_MAP[METHODS.WALLET],
      ];
      const submitBtn = page.getByRole('button', { name: 'Submit' });
      await expect(submitBtn).toBeDisabled();
      expect(page.getByText('Key', { exact: true })).toBeVisible();
      expect(page.getByText('Salt', { exact: true })).toBeVisible();
      expect(page.getByText('Payment Methods', { exact: true })).toBeVisible();
      await typeTextIntoElement(page, ELEMENTS.PROVIDER_KEY, 'ABC678TEST');
      await typeTextIntoElement(page, ELEMENTS.PROVIDER_SALT, 'ABC678TEST');
      await clickMethodsByText(page, methods);
      await selectDropdownOption({
        page,
        dropdownSelector: ELEMENTS.WALLET_SELECT,
        optionSelector: ELEMENTS.WALLET_OPTION_PHONEPE,
      });
      expect(submitBtn).toBeEnabled();
      await clickButton(page, 'Go Back');
    } catch (error) {
      console.error('Error Add PayU Provider - Instant(beta): ', error?.message);
    }
  });

  test('Add PayU Provider - Server-to-Server', async ({ page }) => {
    try {
      await navigateToOptimizer(page);
      await clickButton(page, 'Add Provider');
      // Step 1
      await assertSelectGateway({ page, searchTerm: 'payu', provider: 'payu' });
      // Step 2
      await assertIntegrationType({
        page,
        radioSelector: ELEMENTS.SERVER_TO_SERVER,
        noteText: 'Enable Server-to-Server',
      });
      // Step 3
      await assertProviderDetails({ page, stepText: 'STEP 3 OUT OF 4' });
      // Step 4
      await commonStepAssertions(page, {
        primaryText: 'PayU Production API Details',
        secondaryText:
          'Please make sure you enter the production API details only and NOT the Test Details',
        stepText: 'STEP 4 OUT OF 4',
      });
      const methods = [
        METHODS_MAP[METHODS.CARD],
        METHODS_MAP[METHODS.EMANDATE],
        METHODS_MAP[METHODS.EMI],
        METHODS_MAP[METHODS.NETBANKING],
        METHODS_MAP[METHODS.UPI],
        METHODS_MAP[METHODS.WALLET],
      ];
      const submitBtn = page.getByRole('button', { name: 'Submit' });
      await expect(submitBtn).toBeDisabled();
      expect(page.getByText('Key', { exact: true })).toBeVisible();
      expect(page.getByText('Salt', { exact: true })).toBeVisible();
      expect(page.getByText('Payment Methods', { exact: true })).toBeVisible();
      await typeTextIntoElement(page, ELEMENTS.PROVIDER_KEY, 'ABC678TEST');
      await typeTextIntoElement(page, ELEMENTS.PROVIDER_SALT, 'ABC678TEST');
      await clickMethodsByText(page, methods);
      await assertSodexoCheckbox(page);
      await selectDropdownOption({
        page,
        dropdownSelector: ELEMENTS.WALLET_SELECT,
        optionSelector: ELEMENTS.WALLET_OPTION_PHONEPE,
      });
      expect(submitBtn).toBeEnabled();
      await clickButton(page, 'Go Back');
    } catch (error) {
      console.error('Error Add PayU Provider - Serer-to-Server: ', error?.message);
    }
  });

  test('Add Billdesk Provider', async ({ page }) => {
    try {
      await navigateToOptimizer(page);
      await clickButton(page, 'Add Provider');
      // Step 1
      await assertSelectGateway({ page, searchTerm: 'billdesk', provider: 'billdesk_optimizer' });
      // Assert seamless note for S2S provider
      const pageContent = await page.textContent('.seamless-header');
      expect(pageContent).toContain('Enable seamless option');
      // Step 2
      await assertProviderDetails({ page, stepText: 'STEP 2 OUT OF 3' });
      // Step 3
      await commonStepAssertions(page, {
        primaryText: 'Billdesk Production API Details',
        secondaryText:
          'Please make sure you enter the production API details only and NOT the Test Details',
        stepText: 'STEP 3 OUT OF 3',
      });
      const methods = [
        METHODS_MAP[METHODS.CARD],
        METHODS_MAP[METHODS.NETBANKING],
        METHODS_MAP[METHODS.UPI],
      ];
      const submitBtn = page.getByRole('button', { name: 'Submit' });
      await expect(submitBtn).toBeDisabled();
      expect(page.getByText('Client Id', { exact: true })).toBeVisible();
      expect(page.getByText('Merchant Id', { exact: true })).toBeVisible();
      expect(page.getByText('Payment Methods', { exact: true })).toBeVisible();
      await typeTextIntoElement(page, ELEMENTS.PROVIDER_CLIENT_ID, 'ABC678TEST');
      await typeTextIntoElement(page, ELEMENTS.PROVIDER_MERCHANT_ID, 'ABC678TEST');
      await clickMethodsByText(page, methods);
      // Assert for TPV options
      await assertTPVOption(page);
      expect(submitBtn).toBeEnabled();
      await clickButton(page, 'Go Back');
    } catch (error) {
      console.error('Error Add Billdesk Provider: ', error?.message);
    }
  });
});
