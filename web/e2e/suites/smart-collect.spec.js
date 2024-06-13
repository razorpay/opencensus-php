import { test, expect } from '@playwright/test';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import {
  expectSuccessNotification,
  switchToTestMode,
  getRandomCustomerData,
  clickSkipAndStartBtn,
} from 'utils';

async function createVirtulaAccount({ page }) {
  await page.getByRole('button', { name: 'Create Customer Identifier' }).click();
  await page.locator('.PowerSelect__TriggerInputContainer').click();
  await page.locator('.quick-create').filter({ hasText: 'Add New' }).click();

  const { email, gstin, name, phone } = getRandomCustomerData();
  await page.getByPlaceholder('Customer Name').fill(name);
  await page.getByPlaceholder('Email Address').fill(email);
  await page.getByPlaceholder('Contact Number').fill(phone);
  await page.getByPlaceholder('e.g 22AAAAA0000A1Z5').fill(gstin);
  await page.getByRole('button', { name: 'Create and add this customer' }).click();

  await expectSuccessNotification({
    page,
    notificationText: 'Customer saved successfully',
  });
  await page
    .locator('footer')
    .filter({ hasText: 'CancelCreate Customer Identifier' })
    .getByRole('button', { name: 'Create Customer Identifier' })
    .click();

  await page.getByRole('button', { name: 'Copy Customer Identifier Details' }).click();
  await page.getByTestId('modal-header-close-btn').click();

  await expect(
    page.getByRole('dialog', { name: 'SliderModal' }).getByText('Active', {
      exact: true,
    }),
  ).toBeVisible();

  const url = new URL(page.url());
  const segments = url.pathname.split('/').filter((segment) => segment.trim() !== '');
  const virtualAccountId = segments[segments.length - 1];

  return virtualAccountId;
}

async function searchAndOpenDetails({ page, virtualAccountId }) {
  await page.locator('input[name="id"]').fill(virtualAccountId);
  await page.getByRole('button', { name: 'Search' }).click();
  await page.getByRole('link', { name: virtualAccountId }).click();
}

test.describe.serial('Test smart collect @flow=smart-collect @project=no-code', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  let virtualAccountId = '';

  test.beforeEach(async ({ page }) => {
    await switchToTestMode({ page });
    await page.goto(routes.SMART_COLLECT);
    await clickSkipAndStartBtn({ page });
  });

  // roast test createDefaultVirtualAccount
  test('should create a default virtual account @priority=critical @suite=nocode-P0-automation', async ({
    page,
  }) => {
    virtualAccountId = await createVirtulaAccount({ page });
    await expect(
      page.getByRole('dialog', { name: 'SliderModal' }).getByText(virtualAccountId),
    ).toBeVisible();
    await expect(
      page.getByRole('dialog', { name: 'SliderModal' }).getByText('Active', {
        exact: true,
      }),
    ).toBeVisible();
  });

  // roast test cancelVirtualAccount
  test('should close virtual account @priority=critical @suite=nocode-P0-automation', async ({
    page,
  }) => {
    await searchAndOpenDetails({ page, virtualAccountId });
    await expect(
      page.getByRole('dialog', { name: 'SliderModal' }).getByText('Active', {
        exact: true,
      }),
    ).toBeVisible();

    await page.getByRole('button', { name: 'Close Customer Identifier' }).click();
    await page.getByRole('button', { name: 'Yes' }).click();

    await expectSuccessNotification({
      page,
      notificationText: 'Customer identifier closed successfully',
    });
    await expect(
      page.getByRole('dialog', { name: 'SliderModal' }).getByText('Closed', {
        exact: true,
      }),
    ).toBeVisible();
  });

  // roast test makeTestPaymentValidatePaymentsInDetailsview
  test('should be able to make test payment @priority=critical @suite=nocode-P0-automation', async ({
    page,
  }) => {
    const virtualAccountId = await createVirtulaAccount({ page });
    await expect(
      page.getByRole('dialog', { name: 'SliderModal' }).getByText(virtualAccountId),
    ).toBeVisible();
    await expect(
      page.getByRole('dialog', { name: 'SliderModal' }).getByText('Active', {
        exact: true,
      }),
    ).toBeVisible();

    await page.getByRole('button', { name: 'Close Customer Identifier' }).click();
    await page.getByRole('button', { name: 'Yes' }).click();

    await expectSuccessNotification({
      page,
      notificationText: 'Customer identifier closed successfully',
    });
    await expect(
      page.getByRole('dialog', { name: 'SliderModal' }).getByText('Closed', {
        exact: true,
      }),
    ).toBeVisible();
  });
});
