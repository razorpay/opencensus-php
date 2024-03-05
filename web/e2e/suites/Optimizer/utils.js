import { expect } from '@playwright/test';
import { routes } from 'testConstants';
import { navigateTo } from 'utils/common';

import { METHODS } from './constants';

// helpers - start
export const clickButton = async (page, buttonText) => {
  const button = await page.locator(`button:has-text("${buttonText}")`);
  await button.click();
};

export const clickCheckbox = async (page, method) => {
  try {
    const methodCheckbox = page.getByText(method, { exact: true });
    await methodCheckbox.click();
  } catch (error) {
    throw new Error(`Checkbox "${method}" not found.`, error?.message);
  }
};

export const typeTextIntoElement = async (page, selector, text) => {
  try {
    const element = await page.$(selector);
    if (!element) {
      throw new Error(`Element with selector "${selector}" not found.`);
    }

    await element.fill(text);
  } catch (error) {
    console.error(`Error typing into element with selector "${selector}":`, error.message);
  }
};

export const assertAriaSelected = async (element, expectedBoolean) => {
  const ariaSelectedValue = await element.getAttribute('aria-selected');
  expect(ariaSelectedValue).toBe(expectedBoolean.toString());
};

export const commonStepAssertions = async (page, textObj) => {
  await Promise.all(
    Object.values(textObj).map(async (text) => {
      await expect(page.getByText(text, { exact: true })).toBeVisible();
    }),
  );
};

export const clickMethodsByText = async (page, methods = []) => {
  await Promise.all(
    methods.map(async (method) => {
      const checkbox = page.getByText(method, { exact: true });
      await checkbox.click();
    }),
  );
};

export const selectDropdownOption = async ({ page, dropdownSelector, optionSelector }) => {
  // Open the dropdown
  const dropdown = await page.$(dropdownSelector);

  if (!dropdown) {
    throw new Error(`Dropdown with selector "${dropdownSelector}" not found.`);
  }

  await dropdown.click();
  // Select an option
  const optionToSelect = await page.$(optionSelector);

  if (!optionToSelect) {
    throw new Error(`Dropdown option with selector "${optionSelector}" not found.`);
  }

  // Verify the selected option
  await assertAriaSelected(optionToSelect, true);
  // Unselect the option
  await optionToSelect.click();
  // Verify the option is unselected
  await assertAriaSelected(optionToSelect, false);
  // Close the dropdown (optional)
  await dropdown.click();
};

export const expectRadio = async (page, selector, expectedBoolean) => {
  try {
    await expect(page.$eval(selector, (radio) => radio.getAttribute('aria-checked'))).resolves.toBe(
      expectedBoolean.toString(),
    );
  } catch (error) {
    throw new Error(`Error in expectRadio for selector "${selector}": ${error.message}`);
  }
};

export const expectTooltipContent = async ({ page, iconSelector, content }) => {
  try {
    const icon = page.locator(iconSelector);
    expect(icon).toBeVisible();
    const tooltipSelector = '.rzp-tooltip-inner';
    await page.waitForSelector(tooltipSelector);
    const tooltipContent = await page.textContent(`${tooltipSelector} .rzp-popover-body`);
    expect(tooltipContent).toContain(content);
  } catch (error) {
    console.error(`Tooltip icon with selector "${iconSelector}" not found.`);
    throw error;
  }
};
// helpers - end

// TODO: @parth-p-ui Check commented code and resolve
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

// Optimizer > AddProviderV2
export const selectGatewayBySearch = async ({ page, searchTerm, provider }) => {
  const searchGatewayInput = page.getByPlaceholder('Search for a gateway');
  expect(searchGatewayInput).toBeVisible();
  await searchGatewayInput.fill(searchTerm);
  await page.waitForSelector(`[data-testid="${provider}"]`, { timeout: 3000 });
  const providerElement = await page.$(`[data-testid="${provider}"]`);
  if (providerElement) {
    await providerElement.click();
  } else {
    throw new Error(`Provider with data-testid="${provider}" not found.`);
  }
};
