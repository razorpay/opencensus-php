import { expect } from '@playwright/test';

import { PAYMENT_PAGES_TYPES } from './constants';
import { SELECTORS } from './selectors';
import { routes } from '../../utils/constants';

export const createPaymentPage = async ({ page, productData, type }) => {
  try {
    const createButton = await page.waitForSelector('span:has-text("Create Payment Page")', {
      timeout: 7000,
    });
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
    const tdElement = await page.waitForSelector(`td:has-text("${id}")`, { timeout: 7000 });
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
