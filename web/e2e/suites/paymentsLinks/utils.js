import { expectSuccessNotification, fillExpiry, generateRandomText } from '../../utils';
import { expect } from '@playwright/test';
import { COMMON_SELECTORS } from '../../utils/selectors';

const SELECTORS = {
  detailsContainer: '.list-group.details-row-container',
  legacyLinkReceiptInput: '.PaymentLinks--Create-Form input[name="receipt"]',
};

export const createPaymentLink = async ({ page, productData, type }) => {
  let upiLink, standardLink, legacyLink;
  switch (type) {
    case 'V2': {
      standardLink = true;
      break;
    }
    case 'V1': {
      legacyLink = true;
      break;
    }
    case 'UPI': {
      upiLink = true;
      break;
    }
    default: {
      //
    }
  }
  const isV2PL = upiLink || standardLink;

  // create CTA is same for both v1 and v2 PL
  await page.getByRole('link', { name: 'Create Payment Link' }).click();

  const referenceId = generateRandomText(12);

  // if its a v2 PL i.e. standard or upi we need to select the option to open modal
  // if its v1 then the modal should be already opened
  if (isV2PL) {
    if (upiLink) {
      await page.getByText(/UPI Payment Link/).click();
    } else {
      await page.getByText(/Standard Payment Link/).click();
    }
  }

  const amount = `${productData.amount / 100}`;
  await page.locator('input[name="amount"]').fill(amount);

  if (productData.accept_partial) {
    // upiLink does not have partial feature
    if (!upiLink) {
      await page
        .locator('label')
        .filter({ hasText: 'Enable Partial Payment' })
        .locator('div')
        .first()
        .click();
      if (legacyLink) {
        await page
          .locator('input[name="first_payment_min_amount"]')
          .fill(productData.first_min_partial_amount);
      }
    }
  }

  await page.getByPlaceholder('Payment description').fill(productData.description);
  if (productData.customer) {
    await page
      .getByPlaceholder(legacyLink ? 'Mobile' : '+91 9876543210')
      .fill(productData.customer.contact);
    await page
      .getByPlaceholder(legacyLink ? 'Email' : 'john@example.com')
      .fill(productData.customer.email);
  }

  if (legacyLink) {
    await page.locator(SELECTORS.legacyLinkReceiptInput).fill(referenceId);
  } else {
    await page.getByPlaceholder('123456').fill(referenceId);
  }

  if (productData.expire_by) {
    if (legacyLink) {
      await page.locator('label').filter({ hasText: 'No Expiry' }).locator('div').first().click();
    }
    await fillExpiry({ page, expire_by: productData.expire_by });
  }
  if (productData.reminder_enable) {
    // TBD
  }
  if (productData.additional_description.length) {
    await page.getByRole('button', { name: '+ Add New' }).click();
    await page.getByPlaceholder('Title (key)').fill(productData.additional_description[0].label);
    await page
      .getByPlaceholder('Description (value)')
      .fill(productData.additional_description[0].content);
  }

  await page.getByRole('button', { name: 'Create Payment Link' }).click();
  await expectSuccessNotification({ page, notificationText: 'Payment link created successfully.' });
  await page.waitForSelector(COMMON_SELECTORS.successNotification, { state: 'hidden' });
  console.log(`PL Link created with referenceId: ${referenceId}`);
  return referenceId;
};

export const searchPLAndOpenDetails = async ({ page, referenceId }) => {
  const searchBtn = await page.getByRole('button', { name: 'Search' });
  expect(searchBtn).toBeVisible();
  await searchBtn.click();
  await page.locator('input[name="receipt"]').fill(referenceId);
  await searchBtn.click();
  await expect(await page.getByRole('cell', { name: referenceId })).toBeVisible();
  const plRecordRow = await page.$(`tr:has(td:has-text("${referenceId}"))`);
  if (!plRecordRow) {
    console.log(`Not able to find PL Record with reference Id ${referenceId}`);
    return;
  }
  const linkElement = await plRecordRow.$('a[href^="/app/paymentlinks/"]');
  if (!linkElement) {
    console.log(`Not able to find link element for PL Record with reference Id ${referenceId}`);
    return;
  }
  await linkElement.click();
};

export const verifyPLCreated = async ({
  page,
  productData,
  referenceId,
  statusToVerify = 'Created',
}) => {
  const detailsContainer = await page.locator(SELECTORS.detailsContainer);
  await expect(await detailsContainer.getByText(statusToVerify, { exact: true })).toBeVisible();
  await expect(await detailsContainer.getByText(referenceId)).toBeVisible();
  await expect(
    await detailsContainer.getByText(productData.description, { exact: true }),
  ).toBeVisible();
  await expect(
    await detailsContainer.getByText(productData.customer.contact, { exact: true }),
  ).toBeVisible();
  await expect(
    await detailsContainer.getByText(productData.customer.email, { exact: true }),
  ).toBeVisible();
};

export const cancelPLCreated = async ({ page }) => {
  const detailsContainer = await page.locator(SELECTORS.detailsContainer);
  const cancelButton = await detailsContainer.getByRole('button', { name: 'Cancel Link' });
  await expect(cancelButton).toBeVisible();
  await cancelButton.click();
  await page.getByRole('button', { name: 'Yes, Cancel' }).click();

  await expectSuccessNotification({
    page,
    notificationText: 'Link Cancelled!',
  });

  const paymentLinkDetailsURL = await page.url();
  await page.goto(paymentLinkDetailsURL);

  await expect(await detailsContainer.getByText('Cancelled')).toBeVisible();
};

export const clonePLCreated = async ({ page, productData, isClassic = false }) => {
  const cloneButton = await page.locator('i.i-copy');

  await expect(cloneButton).toBeVisible();
  await cloneButton.click();

  const amount = `${productData.amount / 100}`;
  await expect(await page.locator('input[name="amount"]').inputValue()).toBe(amount);
  await expect(await page.getByPlaceholder('Payment description').inputValue()).toBe(
    productData.description,
  );
  if (productData.customer) {
    await expect(
      await page.getByPlaceholder(isClassic ? 'Mobile' : '+91 9876543210').inputValue(),
    ).toBe(productData.customer.contact);
    await expect(
      await page.getByPlaceholder(isClassic ? 'Email' : 'john@example.com').inputValue(),
    ).toBe(productData.customer.email);
  }

  const referenceId = generateRandomText(12);
  if (isClassic) {
    await page.locator(SELECTORS.legacyLinkReceiptInput).fill(referenceId);
  } else {
    await page.getByPlaceholder('123456').fill(referenceId);
  }

  await page.getByRole('button', { name: 'Create Payment Link' }).click();
  await expectSuccessNotification({
    page,
    notificationText: 'Payment link created successfully.',
  });
};

export const editPLCreated = async ({ page, productData, referenceId }) => {
  const referenceIdChangeButton = await page.waitForSelector(
    'div.pair-label:has-text("Reference Id") + div.pair-value button.Button',
  );
  await referenceIdChangeButton.click();
  await page.getByPlaceholder('Reference Id').fill(`${referenceId}NEW`);
  await page.getByRole('button', { name: 'Save' }).click();
  await expectSuccessNotification({
    page,
    notificationText: 'Receipt is updated successfully',
  });

  const addNotesButton = await page.getByRole('button', { name: '+ Add New' });
  await addNotesButton.click();
  await page
    .getByPlaceholder('Title (key)')
    .fill(`${productData.additional_description[0].label}NEW`);
  await page
    .getByPlaceholder('Description (value)')
    .fill(`${productData.additional_description[0].content}NEW`);
  await page.getByRole('button', { name: 'Save' }).click();
  await expectSuccessNotification({
    page,
    notificationText: 'Notes are updated successfully',
  });
};

export const searchAndVerifyByStatus = async ({ container, statusToVerify }) => {
  await container.locator('select[name="status"]').selectOption(statusToVerify);
  await container.getByRole('button', { name: 'Search' }).click();
  const firstRow = await container.locator('tbody tr').first();

  // Get the status from the first row
  const firstRowStatusCell = await firstRow.locator('td:nth-child(7)', (cell) =>
    cell.textContent.trim(),
  );
  const firstRowStatus = await firstRowStatusCell.textContent();
  expect(firstRowStatus).toBe(statusToVerify);
};

export const searchAndVerifyByPLId = async ({ container }) => {
  const firstRecord = await container.locator('tbody tr').first();
  const paymentLinkId = await firstRecord.locator('a').textContent();

  await container.locator('input[name="id"]').fill(paymentLinkId);
  await container.getByRole('button', { name: 'Search' }).click();
  const firstRow = await container.locator('tbody tr').first();
  await expect(await firstRow.locator(`td:has-text("${paymentLinkId}")`)).toBeVisible();
};

export const searchAndVerifyByPLReferenceId = async ({ container, referenceId }) => {
  await container.locator('input[name="receipt"]').fill(referenceId);
  await container.getByRole('button', { name: 'Search' }).click();
  const firstRow = await container.locator('tbody tr').first();
  await expect(await firstRow.locator(`td:has-text("${referenceId}")`)).toBeVisible();
};

export const clickSkipAndStartBtn = async ({ page }) => {
  let skipAndStartedButton;
  try {
    skipAndStartedButton = await page.waitForSelector('button:has-text("Skip And Get Started")', {
      timeout: 5000,
    });
  } catch (error) {
    // Element not found within the specified timeout
    // Handle the error or perform alternative actions
  }

  if (skipAndStartedButton) {
    await skipAndStartedButton.click();
    await page.waitForTimeout(1000);
  }
};

export const navigateToPaymentHistory = async ({ page, container, isPartialPaid }) => {
  const firstRow = await container.locator('tbody tr').first();
  await firstRow.locator('a').click();

  const detailsContainer = await page.locator(SELECTORS.detailsContainer);

  if (isPartialPaid) await page.getByText('View Payment Details').click();

  const paymentLink = await detailsContainer.getByText(/^pay_/);
  await expect(paymentLink).toBeVisible();
  const paymentId = await paymentLink.innerText();
  console.log(paymentId);
  await paymentLink.click();

  const paymentDetails = await page.getByTestId('payment-details');
  await expect(await paymentDetails.getByText(paymentId)).toBeVisible();
  await expect(await paymentDetails.getByText('Captured')).toBeVisible();
  return paymentDetails;
};

export const verifyPaymentHistory = async ({ page, container, isPartialPaid }) => {
  const paymentDetails = await navigateToPaymentHistory({
    page,
    container,
    isPartialPaid,
  });
  const orderLink = await paymentDetails.getByText(/^order_/);
  await expect(orderLink).toBeVisible();
  const orderId = await orderLink.innerText();
  console.log(orderId);
  await orderLink.click();

  const txnDetails = await page.locator('.txn-details');
  await expect(await txnDetails.getByText(orderId)).toBeVisible();
  const showHideBtn = await txnDetails.getByRole('button', {
    name: 'Show/Hide',
  });
  await expect(showHideBtn).toBeVisible();
  await showHideBtn.click();
  await expect(await txnDetails.getByText(isPartialPaid ? 'Attempted' : 'Paid')).toBeVisible();
};

export const verifyInvoicePaymentHistory = async ({ page, container, isPartialPaid }) => {
  const paymentDetails = await navigateToPaymentHistory({
    page,
    container,
    isPartialPaid,
  });
  const invocieLink = await paymentDetails.getByText(/^inv_/);
  await expect(invocieLink).toBeVisible();
  const invoiceId = await invocieLink.innerText();
  console.log(invoiceId);
  await invocieLink.click();

  const txnDetails = await page.locator('.txn-details');
  await expect(await txnDetails.getByText(isPartialPaid ? 'Partially Paid' : 'Paid')).toBeVisible();
};
