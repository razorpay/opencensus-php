import { test, expect } from '@playwright/test';

import { expectSuccessNotification, generateRandomText, switchToTestMode } from '../../utils';
import { clickSkipAndStartBtn } from 'utils';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import {
  paymentButtonType,
  createPaymentButton,
  updatePaymentButtonPostPaymentSettings,
  updatePaymentButtonReceiptSettings,
  openBtnDetailsView,
} from './utils';

test.describe.parallel(
  'Test Payments Buttons @flow=payment-buttons @project=no-code-stable',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    });

    test.beforeEach(async ({ page }) => {
      await switchToTestMode({ page });
      await page.goto(routes.PAYMENT_BUTTONS);
      await clickSkipAndStartBtn({ page });
    });

    test.describe.parallel('Create Payment Buttons', () => {
      test('should create Custom Payment buttons @priority=critical', async ({ page }) => {
        const { buttonTitle } = await createPaymentButton({
          page,
          type: paymentButtonType.custom,
        });
        await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
      });

      test('should create Buy now Payment buttons @priority=critical', async ({ page }) => {
        const { buttonTitle } = await createPaymentButton({
          page,
          type: paymentButtonType.buyNow,
        });
        await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
      });

      test('should create Donations Payment buttons @priority=critical', async ({ page }) => {
        const { buttonTitle } = await createPaymentButton({
          page,
          type: paymentButtonType.donations,
          receiptSettings: {
            show80g: true,
          },
        });
        await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
      });

      test('should create Quick Pay Payment buttons @priority=critical', async ({ page }) => {
        const { buttonTitle } = await createPaymentButton({
          page,
          type: paymentButtonType.quickPay,
          receiptSettings: {
            sendAutomatically: true,
          },
        });
        await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
      });

      test('should create Quick Pay Payment button with custom post payment message and url @priority=critical', async ({
        page,
      }) => {
        const { buttonTitle } = await createPaymentButton({
          page,
          type: paymentButtonType.quickPay,
          receiptSettings: {
            sendAutomatically: true,
          },
          postPaymentSettings: {
            addCustomMsg: true,
            addRedirectUrl: true,
          },
        });
        await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
      });
    });

    test.describe.serial('Edit and clone Payment button', () => {
      let testButtonTitle = '';

      test('should edit payment button @priority=critical', async ({ page }) => {
        const { buttonTitle } = await createPaymentButton({
          page,
          type: paymentButtonType.quickPay,
          openDetailsView: true,
        });

        testButtonTitle = buttonTitle;

        const editButton = await page.locator('i.i-edit-outline');
        await expect(editButton).toBeVisible();
        await editButton.click();

        const updatedButtonTitle = generateRandomText(10);
        await page.locator('input[name="title"]').click();
        await page.locator('input[name="title"]').fill(updatedButtonTitle);

        await page.getByPlaceholder('Add your amount').click();
        await page.getByPlaceholder('Add your amount').fill('50');

        await page.locator('input[name="button_text"]').click();
        await page.locator('input[name="button_text"]').fill('Updated label');

        await page.getByRole('button', { name: 'Next', exact: false }).click();
        await page.getByRole('button', { name: 'Next', exact: false }).click();
        await page.getByRole('button', { name: 'Update Button' }).click();

        await page.getByRole('link', { name: 'Back To Dashboard' }).click();
        await page.locator('input[name="title"]').fill(updatedButtonTitle);
        testButtonTitle = updatedButtonTitle;
        await page.getByRole('button', { name: 'Search' }).click();
        await expect(page.getByRole('link', { name: updatedButtonTitle })).toBeVisible();
      });

      test('should update and verify stock @priority=critical', async ({ page }) => {
        await openBtnDetailsView({
          page,
          buttonTitle: testButtonTitle,
        });
        await page.getByRole('button', { name: 'Update Stock' }).click();
        await page.locator('label').filter({ hasText: 'No Limit' }).locator('div').first().click();
        await page.getByPlaceholder('Total Stock').click();

        const newStockValue = '25';

        await page.getByPlaceholder('Total Stock').fill(newStockValue);
        await page.getByRole('button', { name: 'Save' }).click();

        await expectSuccessNotification({
          page,
          notificationText: 'Stock is updated successfully',
        });

        await page.getByRole('button', { name: 'Update Stock' }).click();
        const updatedStockValue = await page.getByPlaceholder('Total Stock').inputValue();
        expect(updatedStockValue).toBe(newStockValue);
      });

      test('should edit payments receipt @priority=critical', async ({ page }) => {
        await openBtnDetailsView({
          page,
          buttonTitle: testButtonTitle,
        });
        const editButton = await page.locator('i.i-edit-outline');
        await expect(editButton).toBeVisible();
        await editButton.click();

        await updatePaymentButtonReceiptSettings({
          page,
          receiptSettings: {
            sendAutomatically: true,
            showCustomerInfo: true,
            show80g: true,
          },
        });
        await page.getByRole('button', { name: 'Save & Update' }).click();

        await expectSuccessNotification({
          page,
          notificationText: 'Receipt settings are updated successfully',
        });
      });

      test('should edit post payments settings @priority=critical', async ({ page }) => {
        await openBtnDetailsView({
          page,
          buttonTitle: testButtonTitle,
        });
        const settingsButton = await page.locator('i.i-settings-outline');
        await expect(settingsButton).toBeVisible();
        await settingsButton.click();
        await page.getByText('Post Payment Actions').click();

        await updatePaymentButtonPostPaymentSettings({
          page,
          postPaymentSettings: {
            addCustomMsg: true,
            addRedirectUrl: true,
            isUpdateFlow: true,
          },
        });
      });

      test('should clone payment button @priority=critical', async ({ page }) => {
        await openBtnDetailsView({
          page,
          buttonTitle: testButtonTitle,
        });

        const cloneButton = await page.locator('i.i-copy');
        await expect(cloneButton).toBeVisible();
        await cloneButton.click();
        const duplicateButtonTitle = await page.locator('input[name="title"]').inputValue();
        expect(duplicateButtonTitle).toBe(testButtonTitle);
        await page.getByRole('button', { name: 'Next', exact: false }).click();
        await page.getByRole('button', { name: 'Next', exact: false }).click();
        await page.getByRole('button', { name: 'Create Button' }).click();
        await page.getByRole('button', { name: 'COPY CODE', exact: false }).click();

        await page.getByRole('link', { name: 'Back To Dashboard' }).click();
      });
    });

    test.describe.serial('Search Payment button', () => {
      let testButtonTitle = '';
      test('should search by title @priority=critical', async ({ page }) => {
        const { buttonTitle } = await createPaymentButton({
          page,
          type: paymentButtonType.quickPay,
        });
        testButtonTitle = buttonTitle;
        console.log('testButtonTitle', buttonTitle);
        await page.locator('input[name="title"]').click();
        await page.locator('input[name="title"]').fill(buttonTitle);

        await page.getByRole('button', { name: 'Search' }).click();

        await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
      });

      test('should search by count @priority=critical', async ({ page }) => {
        await page.getByRole('spinbutton').click();
        await page.getByRole('spinbutton').fill('10');

        await page.locator('input[name="title"]').click();
        await page.locator('input[name="title"]').fill(testButtonTitle);

        await page.getByRole('button', { name: 'Search' }).click();

        await expect(page.getByRole('link', { name: testButtonTitle })).toBeVisible();
      });

      test('should search by active status @priority=critical', async ({ page }) => {
        await page.getByRole('combobox').selectOption('Active');

        await page.locator('input[name="title"]').click();
        await page.locator('input[name="title"]').fill(testButtonTitle);

        await page.getByRole('button', { name: 'Search' }).click();

        await expect(page.getByRole('link', { name: testButtonTitle })).toBeVisible();
      });

      test('should search by inactive status @priority=critical', async ({ page }) => {
        await openBtnDetailsView({
          page,
          buttonTitle: testButtonTitle,
        });
        await page.getByRole('button', { name: 'Deactivate' }).click();
        await page.getByRole('button', { name: 'Yes, deactivate' }).click();

        await page.getByRole('button', { name: 'Get Code' }).click();
        await page.getByRole('link', { name: 'Back To Dashboard' }).click();

        await page.getByRole('combobox').selectOption('Inactive');
        await page.getByRole('button', { name: 'Search' }).click();

        await expect(page.getByRole('link', { name: testButtonTitle })).toBeVisible();
      });
    });

    test.describe.serial('Correct data and actions', () => {
      let testButtonTitle = '';
      let testButtonId = '';

      test('should show and copy payment button code in list view @priority=critical', async ({
        page,
      }) => {
        const { buttonTitle, buttonId } = await createPaymentButton({
          page,
          type: paymentButtonType.quickPay,
        });

        testButtonTitle = buttonTitle;
        testButtonId = buttonId;

        await page
          .getByTestId(`entity-item-row-${testButtonId}`)
          .getByText('GET BUTTON CODE')
          .click();
        await page.getByRole('button', { name: 'COPY CODE' }).click();
        await page.getByRole('link', { name: 'Back To Dashboard' }).click();
      });

      test('should show and copy payment button code in details view @priority=critical', async ({
        page,
      }) => {
        await openBtnDetailsView({
          page,
          buttonTitle: testButtonTitle,
        });
        await page.getByRole('button', { name: 'Get Code' }).click();
        await page.getByRole('button', { name: 'COPY CODE' }).click();
        await page.getByRole('link', { name: 'Back To Dashboard' }).click();
      });

      test('should show correct info in details view @priority=critical', async ({ page }) => {
        await openBtnDetailsView({
          page,
          buttonTitle: testButtonTitle,
        });

        await expect(
          page.getByText(testButtonId, {
            exact: true,
          }),
        ).toBeVisible();
        await expect(
          page.getByText(testButtonTitle, {
            exact: true,
          }),
        ).toBeVisible(0);
        await expect(
          page.getByText('Active', {
            exact: true,
          }),
        ).toBeVisible();
      });

      test('should download report @priority=critical', async ({ page }) => {
        await openBtnDetailsView({
          page,
          buttonTitle: testButtonTitle,
        });
        await page.getByText('Download Report').click();

        await expect(page.getByText('CSV')).toBeVisible();
        await expect(page.getByText('Excel (xlsx)')).toBeVisible();
        await expect(page.getByText('Old Excel (xls)')).toBeVisible();
      });
    });
  },
);
