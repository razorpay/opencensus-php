import { test, expect } from 'utils/base';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { clickSkipAndStartBtn } from 'utils';

import {
  paymentButtonType,
  createPaymentButton,
  updatePaymentButtonPostPaymentSettings,
  updatePaymentButtonReceiptSettings,
  searchButtonTest,
  editTest,
  cloneTest,
  openBtnDetailsView,
} from './utils';
import { expectSuccessNotification, generateRandomText, waitForLoader } from '../../utils';

test.describe
  .parallel('Test Payments Buttons @flow=payment-buttons @project=no-code-stable', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.PAYMENT_BUTTONS);
    await clickSkipAndStartBtn({ page });
  });

  test.describe.parallel('Create Payment Buttons', () => {
    test.skip('should create Custom Payment buttons @priority=critical', async ({ page }) => {
      const { buttonTitle } = await createPaymentButton({
        page,
        type: paymentButtonType.custom,
      });
      await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
    });

    test.skip('should create Buy now Payment buttons @priority=critical', async ({ page }) => {
      const { buttonTitle } = await createPaymentButton({
        page,
        type: paymentButtonType.buyNow,
      });
      await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
    });

    test.skip('should create Donations Payment buttons @priority=critical', async ({ page }) => {
      const { buttonTitle } = await createPaymentButton({
        page,
        type: paymentButtonType.donations,
        receiptSettings: {
          show80g: true,
        },
      });
      await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
    });

    test.skip('should create Quick Pay Payment buttons @priority=critical', async ({ page }) => {
      const { buttonTitle } = await createPaymentButton({
        page,
        type: paymentButtonType.quickPay,
        receiptSettings: {
          sendAutomatically: true,
        },
      });
      await expect(page.getByRole('link', { name: buttonTitle })).toBeVisible();
    });

    test.skip('should create Quick Pay Payment button with custom post payment message and url @priority=critical', async ({
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

  test.describe.parallel('Edit and clone Payment button', () => {
    let testButtonId = editTest.buttonId;

    test('should edit payment button @priority=critical', async ({ page }) => {
      openBtnDetailsView({ page, buttonId: testButtonId, willWaitForLoad: true });
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
      await page.getByRole('button', { name: 'Search' }).click();
      await expect(page.getByRole('link', { name: updatedButtonTitle })).toBeVisible();
    });

    test('should update and verify stock @priority=critical', async ({ page }) => {
      openBtnDetailsView({ page, buttonId: testButtonId });
      await waitForLoader({ page, selector: '.page-spinner-container' });
      await page.getByRole('button', { name: 'Update Stock' }).click();
      const isDisabled = await page.getByPlaceholder('Total Stock').isDisabled();

      if (isDisabled) {
        await page.locator('label').filter({ hasText: 'No Limit' }).locator('div').first().click();
      }

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
      openBtnDetailsView({ page, buttonId: testButtonId, willWaitForLoad: true });
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
      openBtnDetailsView({ page, buttonId: testButtonId, willWaitForLoad: true });
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

    test.skip('should clone payment button @priority=critical', async ({ page }) => {
      const cloneTestButtonId = cloneTest.buttonId;
      const cloneTestButtonTitle = cloneTest.buttontitle;
      openBtnDetailsView({ page, buttonId: cloneTestButtonId, willWaitForLoad: true });
      const cloneButton = await page.locator('i.i-copy');
      await expect(cloneButton).toBeVisible();
      await cloneButton.click();

      const duplicateButtonTitle = await page.locator('input[name="title"]').inputValue();
      await expect(duplicateButtonTitle).toBe(cloneTestButtonTitle);

      await page.getByRole('button', { name: 'Next', exact: false }).click();
      await page.getByRole('button', { name: 'Next', exact: false }).click();
      await page.getByRole('button', { name: 'Create Button' }).click();
      await page.getByRole('button', { name: 'COPY CODE', exact: false }).click();

      await page.getByRole('link', { name: 'Back To Dashboard' }).click();
    });

    test('should active and deactivate payment button @priority=critical', async ({ page }) => {
      openBtnDetailsView({ page, buttonId: testButtonId });
      await waitForLoader({ page, selector: '.page-spinner-container' });
      const statusElement = await page
        .getByTestId('payment-button-status-label')
        .getByText('Active', {
          exact: true,
        });
      const isActiveVisible = await statusElement.isVisible();
      if (isActiveVisible) {
        await page.getByRole('button', { name: 'Deactivate', exact: true }).click();
        await page.getByRole('button', { name: 'Yes, deactivate' }).click();
        await expectSuccessNotification({
          page,
          notificationText: `${testButtonId} is now Inactive`,
        });
      } else {
        await page.getByRole('button', { name: 'Activate', exact: true }).click();
        await page.getByRole('button', { name: 'Yes, activate' }).click();
        await expectSuccessNotification({
          page,
          notificationText: `${testButtonId} is now Active`,
        });
      }
    });
  });

  test.describe.serial('Search Payment button', () => {
    let testButtonTitle = searchButtonTest.activeButtontitle;

    test('should search by title @priority=critical', async ({ page }) => {
      await page.locator('input[name="title"]').click();
      await page.locator('input[name="title"]').fill(testButtonTitle);

      await page.getByRole('button', { name: 'Search' }).click();

      await expect(page.getByRole('link', { name: testButtonTitle, exact: true })).toBeVisible();
    });

    test('should search by count @priority=critical', async ({ page }) => {
      await page.getByRole('spinbutton').click();
      await page.getByRole('spinbutton').fill('10');

      await page.locator('input[name="title"]').click();
      await page.locator('input[name="title"]').fill(testButtonTitle);

      await page.getByRole('button', { name: 'Search' }).click();

      await expect(page.getByRole('link', { name: testButtonTitle, exact: true })).toBeVisible();
    });

    test('should search by active status @priority=critical', async ({ page }) => {
      const activeButtonTitle = searchButtonTest.activeButtontitle;

      await page.locator('input[name="title"]').click();
      await page.locator('input[name="title"]').fill(activeButtonTitle);
      await page.getByRole('combobox').selectOption('Active');

      await page.getByRole('button', { name: 'Search' }).click();

      await expect(page.getByRole('link', { name: activeButtonTitle, exact: true })).toBeVisible();
    });

    test('should search by inactive status @priority=critical', async ({ page }) => {
      const inactiveButtonTitle = searchButtonTest.inactiveButtonTitle;

      await page.locator('input[name="title"]').fill(inactiveButtonTitle);
      await page.getByRole('combobox').selectOption('Inactive');

      await page.getByRole('button', { name: 'Search' }).click();

      await expect(
        page.getByRole('link', { name: inactiveButtonTitle, exact: true }),
      ).toBeVisible();
    });
  });

  test.describe.serial('Correct data and actions', () => {
    let testButtonTitle = searchButtonTest.activeButtontitle;
    let testButtonId = searchButtonTest.activeButtonId;

    test('should show and copy payment button code in list view @priority=critical', async ({
      page,
    }) => {
      await page.locator('input[name="title"]').click();
      await page.locator('input[name="title"]').fill(testButtonTitle);
      await page.getByRole('button', { name: 'Search' }).click();

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
      openBtnDetailsView({ page, buttonId: testButtonId, willWaitForLoad: true });
      await page.getByRole('button', { name: 'Get Code' }).click();
      await page.getByRole('button', { name: 'COPY CODE' }).click();
      await page.getByRole('link', { name: 'Back To Dashboard' }).click();
    });

    test('should show correct info in details view @priority=critical', async ({ page }) => {
      openBtnDetailsView({ page, buttonId: testButtonId, willWaitForLoad: true });
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
      openBtnDetailsView({ page, buttonId: testButtonId, willWaitForLoad: true });
      await page.getByText('Download Report').click();

      await expect(page.getByText('CSV')).toBeVisible();
      await expect(page.getByText('Excel (xlsx)')).toBeVisible();
      await expect(page.getByText('Old Excel (xls)')).toBeVisible();
    });
  });
});
