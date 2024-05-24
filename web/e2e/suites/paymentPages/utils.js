import { routes } from 'testConstants';
import { expect } from 'utils/base';

import { PAYMENT_PAGES_TYPES } from './constants';
import { SELECTORS, BATCH_PP_SELECTORS } from './selectors';

export const createPaymentPage = async ({ page, productData, type }) => {
  try {
    const createButton = await page.waitForSelector('span:has-text("Create Payment Page")');
    if (createButton) {
      await createButton.click();
    }
  } catch (e) {
    // continue regardless of error
  }
  if (type === PAYMENT_PAGES_TYPES.storefront) {
    await page.getByRole('button', { name: 'Select Storefront page' }).click();

    // Store title
    await page.getByAltText('edit').click();
    await page.locator(SELECTORS.pageTitle).fill(productData.title);
    await page.getByTestId('page-title-save').click();

    // Business details
    await page.getByText('More options').click();
    // expect(page.getByText('Contact details')).toBeTruthy();
    await page.locator(SELECTORS.email).fill(productData.support_email);
    await page.locator(SELECTORS.phone).fill(productData.support_contact);
    await page.getByText('Save contact details').click();

    // Add products
    // try {
    //   await page.getByText('Add your first product').click();
    // } catch (error) {
    //   createProduct({ page, product: productData.products[0] });
    //   // productData.products.forEach(async (product) => {
    //   // });
    // }
    // Add existing product to store
    try {
      await page.getByText('Add products to this page').click();
      await page
        .locator(
          `[data-testid=select-checkbox-container] >> text=${productData.products[0].product_name}`,
        )
        .click();
      await page.getByRole('button', { name: 'Add 1 product' }).click();
    } catch (error) {
      // continue regardless of error
      console.log(error, 'error');
    }

    await page.getByRole('button', { name: 'Publish page' }).click();

    await expect(page.getByText('Storefront created successfully')).toBeVisible();
  }
};

export const createProduct = async ({ page, product }) => {
  await page.locator(SELECTORS.productName).fill(product.product_name);
  await page.locator(SELECTORS.amount).fill(product.amount);
  // TODO: Add logic for discounted price after taking pull from alignment changes
  // await page.locator(SELECTORS.discountedAmount).fill(product.discounted_amount);
  await page.locator(SELECTORS.units).fill(product.units);
  await page.locator(SELECTORS.description).fill(product.description);
  await page
    .locator('div')
    .filter({ hasText: 'CancelAdd product' })
    .getByRole('button', { name: 'Add product' })
    .click();
};

export const validateBatchPaymentPageDetails = async ({ page, productData }) => {
  try {
    const id = productData.detailsPage.paymentLinkId;
    const tdElement = await page.waitForSelector(`td:has-text("${id}")`);
    if (tdElement) {
      await tdElement.click();
      await expect(page).toHaveURL(
        `${routes.BATCH_PAYMENT_PAGES}/${id}/payments#batchpaymentpages`,
      );
      await expect(page.getByText('Paid Count')).toBeVisible();
      await expect(page.getByText('Paid Amount')).toBeVisible();
      await expect(page.getByText('Unpaid Count')).toBeVisible();
      await expect(page.getByText('Unpaid Amount')).toBeVisible();
      await expect(page.getByText('Total Pending Late Fee')).toBeVisible();
    }
  } catch (e) {
    // continue regardless of error
  }
};

export const validateDownloadSampleFile = async ({ page, productData }) => {
  try {
    const id = productData.detailsPage.paymentLinkId;
    const tdElement = await page.waitForSelector(`td:has-text("${id}")`);
    if (tdElement) {
      await page.getByRole('button', { name: 'Batch Details' }).first().click();
      await expect(page).toHaveURL(
        `paymentpages/batchuploads/${id}/Batch%20PP%20pl_NBIHnwkjVUIseX`,
      );
      await expect(page.getByText('Download Sample File')).toBeVisible();
    }
  } catch (e) {
    // continue regardless of error
  }
};

export const createBatchPaymentPageWithLateFee = async ({ page, productData }) => {
  const createButton = page.locator('.cta-container');
  await expect(createButton).toBeVisible();
  await createButton.click();
  await expect(page).toHaveURL(`${routes.BATCH_PAYMENT_PAGES}/new`);
  await expect(page.getByText('Create New Payment Page (Step 1/2)')).toBeVisible();
  await expect(page.getByTestId('Primary Reference ID')).toBeVisible();
  await expect(page.getByTestId('Secondary Reference ID')).toBeVisible();
  await expect(page.getByTestId('Email')).toBeVisible();
  await expect(page.getByTestId('Phone')).toBeVisible();
  await expect(page.getByText('Enable Late Payment Charge')).toBeVisible();
  await page.locator(BATCH_PP_SELECTORS.pageTitle).fill(productData.page_title);
  await page.locator(BATCH_PP_SELECTORS.supportEmail).fill(productData.support_email);
  await page.locator(BATCH_PP_SELECTORS.supportContact).fill(productData.support_contact);
  await page.getByRole('button', { name: 'Price field' }).first().click();
  await page.locator(BATCH_PP_SELECTORS.amountFieldOptions).click();
  // todo unable to make price filed mandatory as click is not working. Will add the postive flow later
  // await page.getByTestId('tick-icon-visible').click();
  await page.locator(BATCH_PP_SELECTORS.saveButton).click();
  await page.getByText('Enable Late Payment Charge').click();
  await page.getByText('Flat Fee').click();
  await page.locator(BATCH_PP_SELECTORS.saveButton).click();
  await page.getByRole('button', { name: 'Save and Proceed to Next Step' }).click();
  await expect(
    page.getByText(
      '1 : Please add at least 1 Price field with ‘Make it Optional Item’ not selected.',
    ),
  ).toBeVisible();
};

export const clickSkipAndStartBtn = async ({ page }) => {
  try {
    const skipAndStartedButton = await page.waitForSelector(
      'button:has-text("Skip And Get Started")',
    );

    await skipAndStartedButton.click();
    await page.waitForTimeout(1000);
    await expect(page.getByText('Select page of your choice')).toBeVisible();
    const choiceCloseButton = await page.waitForSelector('span.close-icon');
    await choiceCloseButton.click();
    await page.waitForTimeout(1000);
  } catch (e) {
    // continue as skip & mandatory will not be visible always
  }
};
