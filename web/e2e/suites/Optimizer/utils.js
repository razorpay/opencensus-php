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
  const searchGatewayInput = page.getByPlaceholder('Search Gateway');
  await expect(searchGatewayInput).toBeVisible();
  await searchGatewayInput.fill(text);
};

export const providerDetailsValidations = async ({ page, providerName, description }) => {
  await expect(page.getByText('Provider Details')).toBeVisible();
  await expect(
    page.getByText('Add details and select Gateway of your payment provider.'),
  ).toBeVisible();

  await expect(page.getByLabel('Provider Name')).toBeVisible();
  const providerNameInput = page.getByPlaceholder('Provider Name');
  await expect(providerNameInput).toBeVisible();
  providerNameInput.fill(providerName);

  await expect(page.getByLabel('Description')).toBeVisible();
  const descriptionInput = page.getByPlaceholder('Description');
  await expect(descriptionInput).toBeVisible();
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
