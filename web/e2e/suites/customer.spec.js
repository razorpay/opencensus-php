import {
  expectSuccessNotification,
  getRandomCustomerData,
  getI18FormattedPhoneNumber,
} from 'utils';

const { routes, getStorageStatePath, BASE_PATH } = require('testConstants');
const { test, expect } = require('utils/base');

test.describe.serial(
  'Test Create and Edit Customer @flow=customer @project=payments @project=payments-roast',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
    });
    let newlyCreatedCustomer = '';
    // roast test createCustomerTest
    test('should create customer @priority=critical @suite=payments-automation @suite=payments-canary', async ({
      page,
    }) => {
      // go to the customers tab
      await page.goto(routes.CUSTOMERS);

      // wait for new customers button to be visible and click it
      await page.getByRole('button', { name: 'New Customer' }).click();

      // wait for Add Customer Modal to be visible
      await page.getByRole('heading', { name: 'Add Customer' });

      // fill Inputs for customer
      const { email, gstin, name, phone } = getRandomCustomerData();
      await page.getByPlaceholder('Customer Name').fill(name);

      await page.getByPlaceholder('Email Address').fill(email);

      await page.getByPlaceholder('Contact Number').fill(phone);

      await page.getByPlaceholder('e.g 22AAAAA0000A1Z5').fill(gstin);

      await page.getByRole('button', { name: 'Save' }).click();

      await expectSuccessNotification({ page, notificationText: 'Customer saved successfully' });

      // save it in global variable so that we can modify this customer in edit test
      newlyCreatedCustomer = name;

      // expect newly created customer's data to be visible
      await expect(await page.getByText(name)).toBeVisible();
      await expect(
        await page.getByRole('cell', { name: getI18FormattedPhoneNumber(phone) }),
      ).toBeVisible();
      await expect(await page.getByRole('cell', { name: email })).toBeVisible();
    });

    // roast test editCustomerDetailsTest
    test('should edit customer @priority=critical @suite=payments-automation @suite=payments-canary', async ({
      page,
    }) => {
      // go to the customers tab
      await page.goto(routes.CUSTOMERS);

      await page.getByText(newlyCreatedCustomer).click();

      // wait for Edit Customer Modal to be visible
      await page.getByRole('heading', { name: 'Edit Customer' });

      // fill Inputs for customer
      const { name, phone } = getRandomCustomerData();

      // fill new name and phone
      await page.getByPlaceholder('Customer Name').fill(name);

      await page.getByPlaceholder('Contact Number').fill(phone);

      // wait for generate confirm button to be visible and click it
      await page.getByRole('button', { name: 'Save' }).click();

      await expectSuccessNotification({ page, notificationText: 'Customer saved successfully' });

      // expect newly updated customer's data to be visible
      await expect(await page.getByText(name)).toBeVisible();
      await expect(
        await page.getByRole('cell', { name: getI18FormattedPhoneNumber(phone) }),
      ).toBeVisible();
    });
  },
);
