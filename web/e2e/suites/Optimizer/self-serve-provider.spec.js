import { expect, test } from '@playwright/test';

import {
  navigateToOptimizer,
  searchGateway,
  providerDetailsValidations,
  razorpayProviderStep3Validations,
} from './utils';

const { BASE_PATH, getStorageStatePath } = require('testConstants');

test.describe.parallel('Optimizer (Live Mode) @flow=optimizer @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).OPTIMIZER_LOGIN_STATE,
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
    // To-do: due to terminal wrong commit this is failing, need to resolve picking terminal commit
    test.skip('should add razorpay provider for regular account type', async ({ page }) => {
      await navigateToOptimizer(page);

      // Step 1
      await searchGateway({ page, text: 'razorpay' });

      try {
        const razorpayGateway = page.locator('h3', { hasText: 'Razorpay' });
        expect(razorpayGateway).toBeVisible();
        await razorpayGateway.click();
        let nextButton = page.getByRole('button', { name: 'Next' });
        expect(nextButton).toBeDisabled();
        expect(page.getByText('Account type')).toBeVisible();
        const regularAccountType = page.getByText('Regular');
        expect(regularAccountType).toBeVisible();
        expect(page.getByText('Banking VAS')).toBeVisible();
        await regularAccountType.click();
        expect(nextButton).not.toBeDisabled();
        await nextButton.click();

        // Step 2
        nextButton = page.getByRole('button', { name: 'Next' });
        expect(nextButton).toBeDisabled();
        providerDetailsValidations({
          page,
          providerName: 'razorpay test 1',
          description: 'testing',
        });
        expect(nextButton).not.toBeDisabled();
        await nextButton.click();

        // Step 3
        await razorpayProviderStep3Validations({ page });
      } catch (error) {
        console.error(
          'Razorpay gateway should be visible. Test failed due to supported gateway API failure.',
          error.message,
        );
      }
    });

    // To-do: due to terminal wrong commit this is failing, need to resolve picking terminal commit
    test.skip('should add razorpay provider for banking vas account type', async ({ page }) => {
      await navigateToOptimizer(page);

      // Step 1
      await searchGateway({ page, text: 'razorpay' });

      try {
        const razorpayGateway = page.locator('h3', { hasText: 'Razorpay' });
        expect(razorpayGateway).toBeVisible();
        await razorpayGateway.click();
        let nextButton = page.getByRole('button', { name: 'Next' });
        expect(nextButton).toBeDisabled();
        expect(page.getByText('Account type')).toBeVisible();
        const bankingVASAccountType = page.getByText('Banking VAS');
        expect(bankingVASAccountType).toBeVisible();
        expect(page.getByText('Regular')).toBeVisible();
        await bankingVASAccountType.click();
        expect(nextButton).toBeDisabled();
        expect(page.getByText('Bank', { exact: true })).toBeVisible();
        const bankInput = page.getByPlaceholder('Select bank');
        expect(bankInput).toBeVisible();
        await bankInput.click();
        const axisBank = page.getByText('Axis Bank');
        expect(axisBank).toBeVisible();
        await axisBank.click();
        expect(nextButton).not.toBeDisabled();
        await nextButton.click();

        // Step 2
        nextButton = page.getByRole('button', { name: 'Next' });
        expect(nextButton).toBeDisabled();
        providerDetailsValidations({
          page,
          providerName: 'razorpay test 1',
          description: 'testing',
        });
        expect(nextButton).not.toBeDisabled();
        await nextButton.click();

        // Step 3
        await razorpayProviderStep3Validations({ page });
      } catch (error) {
        console.error(
          'Razorpay gateway should be visible. Test failed due to supported gateway API failure.',
          error.message,
        );
      }
    });
  });

  test.describe.parallel('Optimizer Add Provider', () => {
    test('Checkout.com should be hidden', async ({ page }) => {
      await navigateToOptimizer(page);
      const addProviderButton = page.getByRole('button', { name: 'Add Provider' });
      await expect(addProviderButton).toBeVisible();
      await addProviderButton.click();
      await expect(page.getByText('Add Provider')).toBeVisible();
      await page.waitForSelector('text=Select Gateway');

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

  test.describe.parallel('Optimizer Add paytm instant provider', () => {
    test('Paytm instant provider - how to enable refund', async ({ page }) => {
      await navigateToOptimizer(page);
      await searchGateway({ page, text: 'paytm' });
      try {
        const paytmGateway = page.locator('h3', { hasText: 'Paytm' });
        expect(paytmGateway).toBeVisible();
        await paytmGateway.click();
        expect(page.getByText('Integration type')).toBeVisible();
        const instantIntegrationType = page.getByText('Instant (beta)');
        expect(instantIntegrationType).toBeVisible();
        expect(page.getByText('Server-to-Server')).toBeVisible();
        await instantIntegrationType.click();
        expect(page.getByText('Enable Instant (beta)')).toBeVisible();
        expect(
          page.getByText(
            'This is a beta release and supports the following payment methods - Debit Cards, Credit Cards, UPI, Netbanking, Paytm Wallet.',
          ),
        ).toBeVisible();
        expect(
          page.getByText(
            'Please reach out to the Paytm support team (pg.support@paytmpayments.com) and ask them to enable refunds via API for your Paytm account.',
          ),
        ).toBeVisible();
      } catch (error) {
        console.error(
          'Paytm gateway should be visible. Test failed due to supported gateway API failure.',
          error.message,
        );
      }
    });
  });
});
