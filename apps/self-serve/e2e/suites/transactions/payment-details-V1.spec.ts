import { expect, test } from '@playwright/test';
import { getStorageStatePath, routes } from '@dashboard/shared-utils/e2e/constants/paths';
import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
import { BASE_PATH } from '../../constants';

const searchPaymentId = async (page, paymentId) => {
  try {
    // Select the input element by its name attribute
    const paymentIdInput = await page.waitForSelector('input[name="id"]');
    // Fill data into the input field
    await paymentIdInput.fill(paymentId);
    await page.getByRole('button', { name: 'Search' }).click();
  } catch (error: any) {
    throw new Error(`Error searching for payment ID (${paymentId}): ${error?.message}`);
  }
};

const openPaymentDialog = async (page, paymentId) => {
  try {
    // Use a CSS selector to select the <td> with the specified text
    const tdElement = await page.waitForSelector(`td:has-text("${paymentId}")`);

    if (!tdElement) {
      throw new Error(`Could not find <td> with the specified text.`);
    }

    // Find the anchor element within the <td>
    const anchorElement = await tdElement.$('a');

    if (!anchorElement) {
      throw new Error('Anchor element not found within <td>.');
    }

    // Perform a click on the anchor element
    await anchorElement.click();
  } catch (error: any) {
    throw new Error(`Error opening payment dialog for ID (${paymentId}): ${error?.message}`);
  }
};

test.describe
  .parallel('Payments transactions (Live Mode) @flow=transactionsV1 @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).OPTIMIZER_LOGIN_STATE,
  });

  test.describe.parallel('Payment details', () => {
    // There are payments which happens via external PGs using Optimizer
    // and these payments are not settled via razorpay
    // In this case we don't want merchant to create transfer,
    // so we are hiding create transfer button for these payments
    test('should not show create transfer button', async ({ page }) => {
      const PAYMENT_ID = 'pay_KSVCtEwuaVGxjB';
      try {
        await navigateTo(page, routes.PAYMENTS);
        await expect(page).toHaveURL(routes.PAYMENTS);
        await searchPaymentId(page, PAYMENT_ID);
        await openPaymentDialog(page, PAYMENT_ID);
        await expect(page.getByText('Transfer', { exact: true })).toBeVisible();
        expect(page.getByRole('button', { name: 'Create transfer' })).not.toBeVisible();
      } catch (error: any) {
        throw new Error(`Test failed: ${error?.message}`);
      }
    });
  });
});
