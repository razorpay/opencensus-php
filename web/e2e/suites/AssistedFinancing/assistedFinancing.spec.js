const { test, expect } = require('@playwright/test');

const { routes, getStorageStatePath, BASE_PATH } = require('../../constants');

test.describe('Test Assisted financing @flow=assistedFinancing @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).EMAIL_TEST_LOGIN_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.ASSISTED_FINANCING);
    await expect(page).toHaveURL(routes.ASSISTED_FINANCING);
  });

  test('show open send payment link modal after clicking on an emi option', async ({ page }) => {
    await expect(await page.getByText('Assisted Financing').first()).toBeVisible();
    await expect(await page.getByText('Check EMI Options').first()).toBeVisible();
    await Promise.all([page.waitForResponse('**/merchant/methods')]);

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
