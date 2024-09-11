import { expect } from 'utils/base';
import { routes } from 'testConstants';

import {
  expectSuccessNotification,
  generateRandomText,
  generateRandomWebsiteUrl,
} from '../../utils';

export const paymentButtonType = {
  quickPay: 'quickPay',
  custom: 'custom',
  buyNow: 'buyNow',
  donations: 'donations',
};

export const paymentButtonTitle = {
  quickPay: 'Quick-Pay',
  custom: 'Custom',
  buyNow: 'Buy Now',
  donations: 'Donations',
};
export const editTest = {
  buttontitle: 'TZFi6AGdPz',
  buttonId: 'pl_Or2cQzXQKRBjxa',
};

export const cloneTest = {
  buttontitle: 'cloneTest-DontDeleteThis',
  buttonId: 'pl_Oto6h8YJJY5KqF',
};

export const searchButtonTest = {
  activeButtonId: 'pl_Or58EGuQAvjRQQ',
  activeButtontitle: 'ActiveButtonTitle-DontDeleteThis',
  inactiveButtonTitle: 'InactiveButtonTitle-DontDeleteThis',
  inactiveButtonId: 'pl_Or5urZY5RBB2KR',
};
export const updatePaymentButtonReceiptSettings = async ({ page, receiptSettings }) => {
  await page.getByRole('button', { name: 'Payment Receipts', exact: false }).click();

  await page
    .getByText(
      receiptSettings.sendAutomatically
        ? 'Send Receipts Automatically'
        : 'Don’t Send Receipts Automatically',
      { exact: true },
    )
    .click();

  if (receiptSettings.show80g) {
    const is80GDetailsCheckBoxDisabled = await page
      .getByText('Show 80g Details on Receipt')
      .isDisabled();

    if (is80GDetailsCheckBoxDisabled) {
      await page.getByText('Add your 80g details').click();
      await page
        .getByPlaceholder(
          'All donations made to us are eligible for tax exemption under 80G of IT act ITBA/EXM/S80G/2019-20/1XXXXXXX Dated DD/MM/YYYY..',
        )
        .fill('Donations are eligible for 80G tax exemptions');
      await page.getByText('Save details').click();
    }

    await page.getByText('Show 80g Details on Receipt').click();
  }
  if (receiptSettings.showCustomerInfo) {
    await page.getByText('Show Customer’s Information on Receipt').click();
  }
};
export const updatePaymentButtonPostPaymentSettings = async ({ page, postPaymentSettings }) => {
  if (postPaymentSettings.addCustomMsg) {
    const isDisabled = await page.getByPlaceholder('Add your message here.').isDisabled();
    if (isDisabled) {
      await page.getByText('Show a custom message').click();
    }
    await page.getByPlaceholder('Add your message here.').click();
    await page.getByPlaceholder('Add your message here.').fill(generateRandomText(40));
  }
  if (postPaymentSettings.addRedirectUrl) {
    const isDisabled = await page.getByPlaceholder('Add redirect URL here').isDisabled();
    if (isDisabled) {
      await page.getByText('Redirect URL').click();
    }
    await page.getByPlaceholder('Add redirect URL here').click();
    await page.getByPlaceholder('Add redirect URL here').fill(generateRandomWebsiteUrl());
  }
  await page.getByRole('button', { name: 'Save' }).click();
  await expectSuccessNotification({
    page,
    notificationText: `${
      postPaymentSettings.isUpdateFlow ? 'Settings' : 'Button settings'
    } are updated successfully`,
  });
};

export const createPaymentButton = async ({
  page,
  type,
  openDetailsView = false,
  receiptSettings = {
    sendAutomatically: false,
    showCustomerInfo: false,
    show80g: false,
  },
  postPaymentSettings = {
    addCustomMsg: false,
    addRedirectUrl: false,
    isUpdateFlow: false,
  },
}) => {
  await page.getByText('Create Payment Button').click();
  await page.getByText(`${paymentButtonTitle[type]} Button`).click();

  const buttonTitle = generateRandomText(10);
  await page.locator('input[name="title"]').click();
  await page.locator('input[name="title"]').fill(buttonTitle);

  switch (type) {
    case paymentButtonType.custom:
    case paymentButtonType.buyNow: {
      await page.getByRole('button', { name: 'Next', exact: false }).click();

      await page.getByRole('button', { name: '+ Add Amount Field' }).click();
      await page.getByRole('listitem').filter({ hasText: 'Fixed Amount' }).click();
      await page.getByPlaceholder('Enter field label').click();
      await page.getByPlaceholder('Enter field label').fill('Amount');
      await page.getByPlaceholder('Enter field label').press('Tab');
      await page.getByPlaceholder('0.00').click();
      await page.getByPlaceholder('0.00').fill('100');
      await page.getByRole('button', { name: 'Save', exact: false }).click();
      break;
    }
    case paymentButtonType.quickPay: {
      await page.getByPlaceholder('Add your amount').click();
      await page.getByPlaceholder('Add your amount').fill('100');
      break;
    }
    case paymentButtonType.donations: {
      await page.getByRole('button', { name: 'Next', exact: false }).click();
      break;
    }
    default: {
      throw new Error('[createPaymentButton]: button type not specified');
    }
  }

  // Submit inital data i.e. amount, title, button type etc.
  await page.getByRole('button', { name: 'Next', exact: false }).click();

  // Submit customer details with default fields i.e. email and phone number
  await page.getByRole('button', { name: 'Next', exact: false }).click();

  // submit form to create button
  await updatePaymentButtonReceiptSettings({ page, receiptSettings });
  await page.getByRole('button', { name: 'Save' }).click();

  await page.getByRole('button', { name: 'Create Button' }).click();
  await page.getByRole('button', { name: 'COPY CODE', exact: false }).click();

  const plId = page.url().match(/\/pl_([a-zA-Z0-9]+)\//)[1];
  const copiedText = await page.evaluate(() => {
    return navigator.clipboard.readText();
  });
  expect(copiedText).toContain(plId);

  const shouldConfigurePostPaymentSettings =
    postPaymentSettings.addCustomMsg || postPaymentSettings.addRedirectUrl;
  if (shouldConfigurePostPaymentSettings) {
    const postPaymentSettingsContainer = await page.locator('.postPayment-settings');
    await postPaymentSettingsContainer.locator('button').click();
    await updatePaymentButtonPostPaymentSettings({ page, postPaymentSettings });
  }

  const match = page.url().match(/\/paymentbuttons\/pl_([^/]+)\/edit/);
  if (!match) {
    throw new Error('button id not present in url in expected pattern');
  }
  const buttonId = `pl_${match[1]}`;

  await page.getByRole('link', { name: 'Back To Dashboard' }).click();
  await page.locator('input[name="title"]').fill(buttonTitle);
  await page.getByRole('button', { name: 'Search' }).click();

  if (openDetailsView) {
    await page.getByRole('link', { name: buttonTitle }).click();
  }

  return { buttonTitle, buttonId };
};

export const openBtnDetailsView = async ({ page, buttonId, willWaitForLoad }) => {
  await page.goto(`${routes.PAYMENT_BUTTONS}/${buttonId}/payments#paymentbuttons`);
  if (willWaitForLoad) {
    await page.waitForLoadState();
  }
};
