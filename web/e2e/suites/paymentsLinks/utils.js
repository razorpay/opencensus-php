import {
  routes,
  expect,
  expectSuccessNotification,
  generateRandomText,
  clickSkipAndStartBtn,
  fillExpiry,
} from '@libs/shared-qsuite/playwright';

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
      await page.getByText(/Collect UPI payments/).click();
    } else {
      await page.locator('.TemplateCard-details >> text="Standard Payment Link"').click();
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
      .fill(`+91 ${productData.customer.contact}`);
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
    await fillExpiry({ page, isLegacyLink: legacyLink });
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
  await clickSkipAndStartBtn({ page });
  const firstRow = await page.locator('[data-testid*="entity-item-row"]').first();
  const firstCell = await firstRow.getByRole('cell').first();
  const paymentsLinkId = await firstCell.textContent();

  return { referenceId, paymentsLinkId };
};

export const searchPLAndOpenDetails = async ({ page, referenceId, paymentsLinkId }) => {
  await expect(
    page.getByRole('link', {
      name: paymentsLinkId,
    }),
  ).toBeVisible();
  const cell = await page.getByRole('cell', { name: referenceId });
  await expect(cell).toBeVisible();
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
  const detailsContainer = page.locator(SELECTORS.detailsContainer);
  await detailsContainer.waitFor();

  const statusElement = await detailsContainer.getByText(statusToVerify, { exact: true });
  await expect(statusElement).toBeVisible();

  const referenceElement = await detailsContainer.getByText(referenceId);
  await expect(referenceElement).toBeVisible();

  const descriptionElement = await detailsContainer.getByText(productData.description, {
    exact: true,
  });
  await expect(descriptionElement).toBeVisible();

  const contactElement = await detailsContainer.getByText(productData.customer.contact);
  await expect(contactElement).toBeVisible();

  const emailElement = await detailsContainer.getByText(productData.customer.email, {
    exact: true,
  });
  await expect(emailElement).toBeVisible();
};

export const cancelPLCreated = async ({ page }) => {
  const detailsContainer = page.locator(SELECTORS.detailsContainer);
  await detailsContainer.waitFor();
  await expect(detailsContainer).toBeVisible();

  const cancelButton = detailsContainer.getByRole('button', { name: 'Cancel Link' });
  await cancelButton.waitFor();
  await expect(cancelButton).toBeVisible();

  await cancelButton.click();
  await page.getByRole('button', { name: 'Yes, Cancel' }).click();
  await expectSuccessNotification({
    page,
    notificationText: 'Link Cancelled!',
  });

  const paymentLinkDetailsURL = await page.url();
  await page.goto(paymentLinkDetailsURL);

  const cancelledElement = await detailsContainer.getByText('Cancelled');
  await expect(cancelledElement).toBeVisible();
};

export const clonePLCreated = async ({ page, productData, isPaymentLinkV1 = false }) => {
  const cloneButton = await page.locator('i.i-copy');

  await expect(cloneButton).toBeVisible();
  await cloneButton.click();

  const amount = `${productData.amount / 100}`;
  await page.waitForTimeout(5000);
  const amountInputValue = await page.locator('input[name="amount"]').inputValue();
  await expect(amountInputValue).toBe(amount);
  const descriptionInputValue = await page.getByPlaceholder('Payment description').inputValue();
  await expect(descriptionInputValue).toBe(productData.description);
  if (productData.customer) {
    await expect(
      await page.getByPlaceholder(isPaymentLinkV1 ? 'Mobile' : '+91 9876543210').inputValue(),
    ).toBe(`+91 ${productData.customer.contact}`);
    await expect(
      await page.getByPlaceholder(isPaymentLinkV1 ? 'Email' : 'john@example.com').inputValue(),
    ).toBe(productData.customer.email);
  }

  const referenceId = generateRandomText(12);
  if (isPaymentLinkV1) {
    await page.locator(SELECTORS.legacyLinkReceiptInput).fill(referenceId);
  } else {
    await page.getByPlaceholder('123456').fill(referenceId);
    await page.getByRole('button', { name: 'Create Payment Link' }).click();
    await expectSuccessNotification({
      page,
      notificationText: 'Payment link created successfully.',
    });
  }
};

export const editPLCreated = async ({ page, productData }) => {
  const referenceIdChangeButton = await page.waitForSelector(
    'div.pair-label:has-text("Reference Id") + div.pair-value button.Button',
  );
  await referenceIdChangeButton.click();
  const referenceId = generateRandomText(12);
  await page.getByPlaceholder('Reference Id').fill(`${referenceId}`);
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
  return { referenceId };
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
  return firstRowStatus;
};

export const searchAndVerifyByPLId = async ({ container, paymentLinksId }) => {
  await container.locator('input[name="id"]').fill(paymentLinksId);
  await container.getByRole('button', { name: 'Search' }).click();
  const firstRow = await container.locator('tbody tr').first();
  const paymentLinkCell = await firstRow.locator(`td:has-text("${paymentLinksId}")`);
  await expect(paymentLinkCell).toBeVisible();
};

export const searchAndVerifyByPLReferenceId = async ({ container, referenceId }) => {
  await container.locator('input[name="receipt"]').fill(referenceId);
  await container.getByRole('button', { name: 'Search' }).click();
  const firstRow = await container.locator('tbody tr').first();

  const referenceCell = await firstRow.locator(`td:has-text("${referenceId}")`);
  await expect(referenceCell).toBeVisible();
};

export const navigateToPaymentHistory = async ({ page, container, isPartialPaid }) => {
  const firstRow = await container.locator('tbody tr').first();
  await firstRow.locator('a').click();

  const detailsContainer = page.locator(SELECTORS.detailsContainer);
  await detailsContainer.waitFor();

  if (isPartialPaid) await page.getByText('View Payment Details').click();

  const paymentLink = await detailsContainer.getByText(/^pay_/);
  await expect(paymentLink).toBeVisible();
  const paymentId = await paymentLink.innerText();
  console.log('paymentId', paymentId);
  await paymentLink.click();

  const paymentDetails = await page.getByTestId('payment-details');
  const paymentIdElement = await paymentDetails.getByText(paymentId);
  await expect(paymentIdElement).toBeVisible();

  const capturedElement = await paymentDetails.getByText('Captured');
  await expect(capturedElement).toBeVisible();

  return paymentDetails;
};

export const verifyPaymentHistory = async ({ page, container }) => {
  const firstRow = await container.locator('tbody tr').first();
  await firstRow.locator('a').click();
  const detailsContainer = await page.locator('.list-group.details-row-container');
  await page.getByText('View Payment Details').click();
  const paymentLink = await detailsContainer.getByText(/^pay_/);
  return paymentLink;
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
  console.log('invoiceId', invoiceId);
  await invocieLink.click();

  const txnDetails = await page.locator('.txn-details');
  const paymentStatus = isPartialPaid ? 'Partially Paid' : 'Paid';
  const statusElement = await txnDetails.getByText(paymentStatus);
  await expect(statusElement).toBeVisible();
};

export const statusToKey = {
  Created: 'created',
  Issued: 'issued',
  'Partially Paid': 'partially_paid',
  Paid: 'paid',
  Cancelled: 'cancelled',
  Expired: 'expired',
};

export const mockLegacyPlId = 'inv_MNqqnqavictor';
export const mockPlId = 'plink_OFripaGIvictor';

export function getPLv2MockResponse({ status }) {
  return {
    accept_partial: true,
    amount: 5000,
    amount_paid: 5000,
    cancelled_at: 0,
    created_at: 1697433548,
    currency: 'INR',
    customer: {
      contact: '7624918474',
      email: 'qa.testing@razorpay.com',
    },
    description: 'With Minimum Partial amount, Customer Details, Receipt No and Expiry',
    expire_by: 1697587199,
    expired_at: 0,
    first_min_partial_amount: 2000,
    id: mockPlId,
    notes: null,
    notify: {
      email: true,
      sms: true,
      whatsapp: false,
    },
    order_id: 'order_MolCugpEZ3PK3l',
    payments: [
      {
        amount: 5000,
        created_at: 1697482814,
        method: 'netbanking',
        payment_id: 'pay_MozC0OFtu3lG1a',
        status: 'captured',
      },
    ],
    reference_id: 'OI0009SAIJ3E',
    reminder_enable: true,
    reminders: {
      status: 'failed',
    },
    short_url: 'https://qa.rzp.io/i/VzcgePx',
    status,
    updated_at: 1697482814,
    upi_link: false,
    user_id: 'GNw8C9TCwgZ3dN',
    whatsapp_link: false,
  };
}

export function getPLv1MockResponse({ status }) {
  return {
    amount: 20000,
    amount_due: 15000,
    amount_paid: 5000,
    billing_end: null,
    billing_start: null,
    cancelled_at: null,
    comment: null,
    created_at: 1691558250,
    currency: 'INR',
    currency_symbol: '\u20b9',
    customer_details: {
      billing_address: null,
      contact: null,
      customer_contact: null,
      customer_email: null,
      customer_name: null,
      email: null,
      gstin: null,
      id: null,
      name: null,
      shipping_address: null,
    },
    customer_id: null,
    date: 1691558250,
    description: 'Partial',
    email_status: 'sent',
    entity: 'invoice',
    expire_by: null,
    expired_at: null,
    first_payment_min_amount: 100,
    gross_amount: 20000,
    group_taxes_discounts: false,
    id: mockLegacyPlId,
    invoice_number: null,
    issued_at: 1691558250,
    line_items: [],
    notes: [],
    order_id: 'order_MNqqoPEqGOd0J1',
    paid_at: null,
    partial_payment: true,
    payment_id: 'pay_MNqrCi3ZIt81Sy',
    payments: {
      count: 1,
      entity: 'collection',
      items: [
        {
          amount: 5000,
          base_amount: 5000,
          captured: true,
          created_at: 1691558276,
          currency: 'INR',
          entity: 'payment',
          id: 'pay_MNqrCi3ZIt81Sy',
          invoice_id: 'inv_MNqqnqaEeWKhxT',
          method: 'netbanking',
          order_id: 'order_MNqqoPEqGOd0J1',
          status: 'captured',
        },
      ],
    },
    receipt: 'Testing!!3',
    reminder_enable: false,
    reminders: [],
    short_url: 'https://qa.rzp.io/i/508RD3AaR',
    sms_status: 'sent',
    status,
    tax_amount: 0,
    taxable_amount: 0,
    terms: null,
    type: 'link',
    user_id: 'FSzkVoWnnZz2Xe',
    view_less: true,
  };
}

export const mockFetchPaymentLinkApi = async ({ targetUrl, mockRespose, page }) => {
  await page.route(targetUrl, async (route) => {
    console.log('Intercepted URL:', route.request().url());
    const modifiedResponseBody = {
      status_code: 200,
      success: true,
      data: mockRespose,
    };

    route.fulfill({
      status: 200,
      headers: 'application/json',
      body: JSON.stringify(modifiedResponseBody),
    });
  });
};

export const openPaymentLinkDetailsView = async ({ page, linkId }) => {
  await page.goto(`${routes.PAYMENT_LINKS}/${linkId}`);
};
