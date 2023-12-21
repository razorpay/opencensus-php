import { expect } from '@playwright/test';

import { navigateTo } from '../../utils/common';
import { routes } from '../../utils/constants';

export const METHODS = {
  CARD: 'card',
  NETBANKING: 'netbanking',
  UPI: 'upi',
};

export const navigateToOptimizer = async (page) => {
  await navigateTo(page, routes.OPTIMIZER);
  // const modalClose = page.locator('span', { hasText: '×', timeout: 000 });
  // if (modalClose) {
  //   await modalClose.click();
  // }
  // const showProducts = page.locator('button', { hasText: 'Show all' });
  // await expect(showProducts).toBeVisible();
  // await showProducts.click();
  // await page.getByRole('link', { name: 'Optimizer' }).click();
  await expect(page).toHaveURL(routes.OPTIMIZER);
};

export const searchGateway = async ({ page, text }) => {
  const addProviderButton = page.getByRole('button', { name: 'Add Provider' });
  await expect(addProviderButton).toBeVisible();
  await addProviderButton.click();
  expect(page.getByText('Add Provider')).toBeVisible();
  expect(page.getByText('Close')).toBeVisible();
  await expect(page.getByText('Select Gateway')).toBeVisible();
  const searchGatewayInput = page.getByPlaceholder('Search Gateway');
  expect(searchGatewayInput).toBeVisible();
  await searchGatewayInput.fill(text);
};

export const providerDetailsValidations = ({ page, providerName, description }) => {
  expect(page.getByText('Provider Details')).toBeVisible();
  expect(page.getByText('Add details and select Gateway of your payment provider.')).toBeVisible();

  expect(page.getByLabel('Provider Name')).toBeVisible();
  const providerNameInput = page.getByPlaceholder('Provider Name');
  expect(providerNameInput).toBeVisible();
  providerNameInput.fill(providerName);

  expect(page.getByLabel('Description')).toBeVisible();
  const descriptionInput = page.getByPlaceholder('Description');
  expect(descriptionInput).toBeVisible();
  descriptionInput.fill(description);
};

export const validateAndEnableMethods = async ({ page, methods = [] }) => {
  const results = [];
  for (const method of methods) {
    const element = page.getByText(method);
    expect(element).toBeVisible();
    results.push(element.click());
  }
  await Promise.all(results);
};

export const razorpayProviderStep3Validations = async ({ page }) => {
  expect(page.getByText('Razorpay Production API Details')).toBeVisible();
  const submitButton = page.getByRole('button', { name: 'Submit' });
  expect(submitButton).toBeDisabled();

  expect(page.getByText('Key', { exact: true })).toBeVisible();
  const keyInput = page.getByPlaceholder('key');
  await expect(keyInput).toBeVisible();
  keyInput.fill('jsadhy6h2');

  expect(page.getByText('Secret', { exact: true })).toBeVisible();
  const secretInput = page.getByPlaceholder('secret');
  await expect(secretInput).toBeVisible();
  secretInput.fill('ajhc6r');

  await expect(page.getByText('Payment Methods', { exact: true })).toBeVisible();
  await validateAndEnableMethods({
    page,
    methods: [METHODS.CARD, METHODS.UPI, METHODS.NETBANKING],
  });

  await expect(submitButton).not.toBeDisabled();
};
