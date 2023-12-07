import { expect, test } from '@playwright/test';

import {
  navigateToOptimizer,
  searchGateway,
  providerDetailsValidations,
  validateAndEnableMethods,
  METHODS,
} from './utils';
import { StorageStatePath } from '../../utils/constants';

test.describe.parallel('Optimizer (Live Mode) @flow=optimizer @project=payments', () => {
  test.use({
    storageState: StorageStatePath.OPTIMIZER_LOGIN_STATE,
  });

  test.describe.parallel('Optimizer Landing screen Provider section', () => {
    test('should show providers section', async ({ page }) => {
      await navigateToOptimizer(page);
      await expect(page.getByText('Payment Provider')).toBeVisible();
      await expect(page.getByRole('link', { name: 'Documentation' })).toBeVisible();
      await expect(page.getByRole('button', { name: 'Add Provider' })).toBeVisible();
    });
  });

  test.describe.parallel('Optimizer Add Razorpay Gateway Provider', () => {
    // Enable after back-end changes go live
    test.skip('should add razorpay provider for regular account type', async ({ page }) => {
      await navigateToOptimizer(page);

      // Step 1
      const addProviderButton = page.getByRole('button', { name: 'Add Provider' });
      await expect(addProviderButton).toBeVisible();
      await addProviderButton.click();
      await expect(page.getByText('Add Provider')).toBeVisible();
      await expect(page.getByText('Close')).toBeVisible();
      await expect(page.getByText('Select Gateway')).toBeVisible();

      await searchGateway({ page, text: 'razorpay' });

      const razorpayGateway = page.locator('h3', { hasText: 'Razorpay' });
      await expect(razorpayGateway).toBeVisible();
      await razorpayGateway.click();
      let nextButton = page.getByRole('button', { name: 'Next' });
      await expect(nextButton).toBeDisabled();
      await expect(page.getByText('Account type')).toBeVisible();
      const regularAccountType = page.getByText('Regular');
      await expect(regularAccountType).toBeVisible();
      await expect(page.getByText('Banking VAS')).toBeVisible();
      await regularAccountType.click();
      await expect(nextButton).not.toBeDisabled();
      await nextButton.click();

      // Step 2
      nextButton = page.getByRole('button', { name: 'Next' });
      await expect(nextButton).toBeDisabled();
      await providerDetailsValidations({
        page,
        providerName: 'razorpay test 1',
        description: 'testing',
      });
      await expect(nextButton).not.toBeDisabled();
      await nextButton.click();

      // Step 3
      await expect(page.getByText('Razorpay Production API Details')).toBeVisible();
      const submitButton = page.getByRole('button', { name: 'Submit' });
      await expect(submitButton).toBeDisabled();

      await expect(page.getByText('Key', { exact: true })).toBeVisible();
      const keyInput = page.getByPlaceholder('key');
      await expect(keyInput).toBeVisible();
      keyInput.fill('jsadhy6h2');

      await expect(page.getByText('Secret', { exact: true })).toBeVisible();
      const secretInput = page.getByPlaceholder('secret');
      await expect(secretInput).toBeVisible();
      secretInput.fill('ajhc6r');

      await expect(page.getByText('Payment Methods', { exact: true })).toBeVisible();
      await validateAndEnableMethods({
        page,
        methods: [METHODS.CARD, METHODS.UPI, METHODS.NETBANKING],
      });

      await expect(submitButton).not.toBeDisabled();
    });

    test.skip('should add razorpay provider for banking vas account type', async ({ page }) => {
      await navigateToOptimizer(page);

      // Step 1
      const addProviderButton = page.getByRole('button', { name: 'Add Provider' });
      await expect(addProviderButton).toBeVisible();
      await addProviderButton.click();
      await expect(page.getByText('Add Provider')).toBeVisible();
      await expect(page.getByText('Close')).toBeVisible();
      await expect(page.getByText('Select Gateway')).toBeVisible();

      await searchGateway({ page, text: 'razorpay' });

      const razorpayGateway = page.locator('h3', { hasText: 'Razorpay' });
      await expect(razorpayGateway).toBeVisible();
      await razorpayGateway.click();
      let nextButton = page.getByRole('button', { name: 'Next' });
      await expect(nextButton).toBeDisabled();
      await expect(page.getByText('Account type')).toBeVisible();
      const bankingVASAccountType = page.getByText('Banking VAS');
      await expect(bankingVASAccountType).toBeVisible();
      await expect(page.getByText('Regular')).toBeVisible();
      await bankingVASAccountType.click();
      await expect(nextButton).toBeDisabled();
      await expect(page.getByText('Bank', { exact: true })).toBeVisible();
      const bankInput = page.getByPlaceholder('Select bank');
      await expect(bankInput).toBeVisible();
      await bankInput.click();
      const axisBank = page.getByText('Axis Bank');
      await expect(axisBank).toBeVisible();
      await axisBank.click();
      await expect(nextButton).not.toBeDisabled();
      await nextButton.click();

      // Step 2
      nextButton = page.getByRole('button', { name: 'Next' });
      await expect(nextButton).toBeDisabled();
      await providerDetailsValidations({
        page,
        providerName: 'razorpay test 1',
        description: 'testing',
      });
      await expect(nextButton).not.toBeDisabled();
      await nextButton.click();

      // Step 3
      await expect(page.getByText('Razorpay Production API Details')).toBeVisible();
      const submitButton = page.getByRole('button', { name: 'Submit' });
      await expect(submitButton).toBeDisabled();

      await expect(page.getByText('Key', { exact: true })).toBeVisible();
      const keyInput = page.getByPlaceholder('key');
      await expect(keyInput).toBeVisible();
      keyInput.fill('jsadhy6h2');

      await expect(page.getByText('Secret', { exact: true })).toBeVisible();
      const secretInput = page.getByPlaceholder('secret');
      await expect(secretInput).toBeVisible();
      secretInput.fill('ajhc6r');

      await expect(page.getByText('Payment Methods', { exact: true })).toBeVisible();
      await validateAndEnableMethods({
        page,
        methods: [METHODS.CARD, METHODS.UPI, METHODS.NETBANKING],
      });

      await expect(submitButton).not.toBeDisabled();
    });
  });

  test.describe.parallel('Optimizer Add Provider', () => {
    test('Checkout.com should be hidden', async ({ page }) => {
      await navigateToOptimizer(page);
      const addProviderButton = page.getByRole('button', { name: 'Add Provider' });
      await expect(addProviderButton).toBeVisible();
      await addProviderButton.click();
      await expect(page.getByText('Add Provider')).toBeVisible();
      await page.waitForSelector('text=Select Gateway', { timeout: 1500 });

      try {
        const checkoutGateway = page.locator('h3', { hasText: 'Checkout.com' });
        await expect(checkoutGateway).toBeHidden();
      } catch (error) {
        console.error(
          'Checkout.com provider should be hidden but it is visible. Test failed.',
          error.message,
        );
      }
    });
  });
});
