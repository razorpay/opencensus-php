import { expect } from '@playwright/test';
import { fillExpiry } from 'utils';

export const fillCustomerDetails = async (page, customerData) => {
  // Wait for 10 seconds before starting to fill the form, due to the delay API response
  await page.waitForTimeout(10000);

  await page.locator(`textarea[name="description"]`).fill(customerData.description);
  await page.locator(`input[name="customerName"]`).fill(customerData.customerName);
  await page.locator(`input[name="customerContact"]`).fill(customerData.customerContact);
  await page.locator(`input[name="customerEmail"]`).fill(customerData.customerEmail);
  await page.locator(`input[name="receipt"].Input-el`).fill(customerData.receipt);

  await page.getByRole('button', { name: 'Next' }).click();
};

export const fillPaymentDetails = async (page, paymentData = {}) => {
  await page.locator('.PowerSelect__Trigger').click();

  const paymentMethodSelector = `.PowerSelect__Option .method >> text=${paymentData.method}`;
  await page.locator(paymentMethodSelector).click();

  await page.locator(`input[name='amount']`).fill(paymentData.amount);

  await page.getByRole('button', { name: 'Next' }).click();
};

export const fillTokenDetails = async (page, tokenData) => {
  await fillExpiry({ page });
  await page.fill('input[name="mandateMaxAmount"]', tokenData.mandateMaxAmount);
  await page.locator(`[data-testid="create-registration-link__create"]`).click();
};

export const verifyRegistrationLinkCreated = async (page, customerData) => {
  await expect(await page.getByText(customerData.receipt, { exact: true })).toBeVisible();
};
