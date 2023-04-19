const { test, expect } = require('@playwright/test');
const { generateRandomPhoneNumber, generateRandomName, generateRandomEmail } = require('../utils');
const { StorageStatePath } = require('../utils/constants');

const CONSTANTS = {
  CUSTOMERS_TAB_URL: '/app/customers',
  NEW_CUSTOMERS_CTA: "xpath=//button[@class='pull-right btn btn-primary']//span",
  CUSTOMER_DATA_TABLE_ROW1_CUSTOMER_ID:
    "xpath=//table[@class='table table-hover']/tbody/tr[1]/td[1]/a",
};

const getRandomCustomerData = () => {
  const phone = generateRandomPhoneNumber();
  const name = generateRandomName();
  const email = generateRandomEmail();
  return {
    name,
    phone: phone.toString(),
    email,
    gstin: '22AAAAA0000A1Z5',
  };
};

test.describe.parallel('Test Create and Edit Customer @flow=customer', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });
  test('should create customer @priority=critical', async ({ page }) => {
    // go to the customers tab
    await page.goto(CONSTANTS.CUSTOMERS_TAB_URL);

    // wait for new customers button to be visible and click it
    await page.waitForSelector(CONSTANTS.NEW_CUSTOMERS_CTA, {
      timeout: 5000,
    });
    await page.locator(CONSTANTS.NEW_CUSTOMERS_CTA).click();

    // wait for Add Customer Modal to be visible
    await page.isVisible('text=Add Customer', { timeout: 5000 });

    // fill Inputs for customer
    const { email, gstin, name, phone } = getRandomCustomerData();
    // fill customer name
    await page.waitForSelector('input[name="name"]', { timeout: 5000 });
    await page.click('input[name="name"]');
    await page.fill('input[name="name"]', name);

    // fill customer email
    await page.waitForSelector('input[name="email"]', { timeout: 5000 });
    await page.click('input[name="email"]');
    await page.fill('input[name="email"]', email);

    // fill customer contact number
    await page.waitForSelector('input[name="contact"]', { timeout: 5000 });
    await page.click('input[name="contact"]');
    await page.fill('input[name="contact"]', phone);

    // fill customer email
    await page.waitForSelector('input[name="gstin"]', { timeout: 5000 });
    await page.click('input[name="gstin"]');
    await page.fill('input[name="gstin"]', gstin);

    // wait for generate confirm button to be visible and click it
    await page.isVisible('button:text("Save")');
    await page.locator('button:text("Save")').click();

    // expect newly created customer's data to be visible
    await expect(page.locator(`text="${name}"`)).toBeVisible();
    await expect(page.locator(`text="${phone}"`)).toBeVisible();
    await expect(page.locator(`text="${email}"`)).toBeVisible();
  });

  test('should edit customer @priority=critical', async ({ page }) => {
    // go to the customers tab
    await page.goto(CONSTANTS.CUSTOMERS_TAB_URL);

    // wait for customer data table to be visible and click on 1st row's customerId
    await page.waitForSelector(CONSTANTS.CUSTOMER_DATA_TABLE_ROW1_CUSTOMER_ID, {
      timeout: 5000,
    });
    await page.locator(CONSTANTS.CUSTOMER_DATA_TABLE_ROW1_CUSTOMER_ID).click();

    // wait for Edit Customer Modal to be visible
    await page.isVisible('text=Edit Customer', { timeout: 5000 });

    // fill Inputs for customer
    const { name, phone } = getRandomCustomerData();

    // fill new name and phone
    // fill customer name
    await page.waitForSelector('input[name="name"]', { timeout: 5000 });
    await page.click('input[name="name"]');
    await page.fill('input[name="name"]', name);

    // fill customer contact number
    await page.waitForSelector('input[name="contact"]', { timeout: 5000 });
    await page.click('input[name="contact"]');
    await page.fill('input[name="contact"]', phone);

    // wait for generate confirm button to be visible and click it
    await page.isVisible('button:text("Save")');
    await page.locator('button:text("Save")').click();

    // expect newly updated customer's data to be visible
    await expect(page.locator(`text="${name}"`)).toBeVisible();
    await expect(page.locator(`text="${phone}"`)).toBeVisible();
  });
});
