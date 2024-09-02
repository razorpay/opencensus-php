import { getStorageStatePath, BASE_PATH } from 'testConstants';
import { expect, test } from 'utils/base';

import { navigateToTransactions } from './utils';

const navigateToRefunds = async (page, refundId) => {
  try {
    await expect(page.getByRole('link', { name: 'Refunds', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Refunds', exact: true }).click();

    // Select the input element by its name attribute
    const refundIdInput = await page.waitForSelector('input[name="id"]');
    // Fill data into the input field
    await refundIdInput.fill(refundId);
    await page.getByRole('button', { name: 'Search' }).click();
  } catch (error) {
    throw new Error(`Error searching for refund ID (${refundId}): ${error?.message}`);
  }
};

const openRefundDialog = async (page, refundId) => {
  try {
    // Use a CSS selector to select the <td> with the specified text
    const tdElement = await page.waitForSelector(`td:has-text("${refundId}")`);

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
  } catch (error) {
    throw new Error(`Error opening refund dialog for ID (${refundId}): ${error?.message}`);
  }
};

const assertGatewayResponse = async (page, refundId) => {
  try {
    const dialogElement = page.getByRole('dialog', { name: 'SliderModal' });
    await expect(dialogElement).toBeVisible();
    await expect(dialogElement.getByText(`Refund Id: ${refundId}`)).toBeVisible();
    await expect(dialogElement.getByText('Payment')).toBeVisible();
    await expect(dialogElement.getByText('pay_N5QHWsLBfF2rsE')).toBeVisible();
    await expect(dialogElement.getByText('Status')).toBeVisible();
    await expect(dialogElement.getByText('Gateway response')).toBeVisible();
  } catch (error) {
    throw new Error(`Error verifying gateway response for ID (${refundId}): ${error?.message}`);
  }
};

test.describe.parallel(
  'Refunds transactions (Test Mode) @flow=transactions @project=payments',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH, 'test').OPTIMIZER_V1_LOGIN_STATE,
    });

    test.describe.parallel('Refunds details', () => {
      test('should show "Gateway Error" details', async ({ page }) => {
        const refundId = 'rfnd_N5QKKDG67Rgnyr';
        try {
          await navigateToTransactions(page);
          await navigateToRefunds(page, refundId);
          await openRefundDialog(page, refundId);
          await assertGatewayResponse(page, refundId);
        } catch (error) {
          console.error(`Test failed: ${error?.message}`);
        }
      });
    });
  },
);
