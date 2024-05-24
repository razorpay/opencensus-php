import { switchToTestMode } from '../../utils';
import { navigateTo } from '../../utils/common';

const { test, expect } = require('utils/base');

const { routes, getStorageStatePath, BASE_PATH } = require('../../constants');

const navigateToAssistedFinancingPage = async (page) => {
  await navigateTo(page, routes.DASHBOARD);
  await switchToTestMode({ page });
  await page.goto(routes.ASSISTED_FINANCING);
  await expect(page).toHaveURL(routes.ASSISTED_FINANCING);
};

test.describe('Test Assisted financing @flow=assistedFinancing @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await navigateToAssistedFinancingPage(page);
  });

  test('show open send payment link modal after clicking on an emi option', async ({ page }) => {
    await Promise.all([page.waitForResponse('**/merchant/methods')]);
    await expect(await page.getByText('Assisted Financing').first()).toBeVisible();
    await expect(await page.getByText('Check EMI Options').first()).toBeVisible();

    await page.getByPlaceholder('9999999999').click();
    await page.getByPlaceholder('9999999999').fill('9876598765');
    await page.getByPlaceholder('0').click();
    await page.getByPlaceholder('0').fill('5500');
    await page.getByRole('button', { name: 'Check Available EMI Options' }).click();

    const fibeCardlessEmi = await page.getByText('fibe Cardless EMI');

    await expect(fibeCardlessEmi).toBeVisible();
    await fibeCardlessEmi.click();

    await page.getByRole('button', { name: 'Send payment link' }).click();
    await page.getByTestId('modal-wrapper').getByPlaceholder('9999999999').click();
    await page.getByTestId('modal-wrapper').getByPlaceholder('9999999999').fill('9876598765');
    await expect(await page.getByTestId('send-payment-link-button')).toBeVisible();
  });
});
