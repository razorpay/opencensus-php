import { test } from '@playwright/test';
import { getStorageStatePath, BASE_PATH, routes } from 'testConstants';

import {
  fillCustomerDetails,
  fillPaymentDetails,
  fillTokenDetails,
  verifyRegistrationLinkCreated,
} from './utils';
import { navigateToInCurlecDashboard } from '../../utils/common';

const REGISTRATION_LINKS_BUTTONS_NAMES_ASSERTIONS = {
  SUBSCRIPTIONS: /Subscriptions/i,
  REGISTRATION_LINK: /Registration Links/i,
  CREATE_NEW_LINK: /Create New Link/i,
};

test.describe.parallel('Create Registration Link @flow=CAW @country=MY', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).CURLEC_TEST_CAW_LOGIN_STATE,
  });

  test('should create a registration link with TNG as payment method', async ({ page }) => {
    await navigateToInCurlecDashboard(page, routes.DASHBOARD);
    await page
      .getByRole('link', { name: REGISTRATION_LINKS_BUTTONS_NAMES_ASSERTIONS.SUBSCRIPTIONS })
      .click();
    await page
      .getByRole('link', { name: REGISTRATION_LINKS_BUTTONS_NAMES_ASSERTIONS.REGISTRATION_LINK })
      .click();
    await page
      .getByRole('link', { name: REGISTRATION_LINKS_BUTTONS_NAMES_ASSERTIONS.CREATE_NEW_LINK })
      .click();

    const currentDate = Date.now();
    const customerData = {
      description: `description ${currentDate}`,
      customerName: 'customerName',
      customerContact: '+61111222333',
      customerEmail: 'customerEmail@customerEmail.com',
      receipt: `receipt ${currentDate}`,
    };
    await fillCustomerDetails(page, customerData);
    await fillPaymentDetails(page, {
      method: 'Wallet',
      amount: '2',
    });
    await fillTokenDetails(page, {
      expire_by: 1,
      mandateMaxAmount: '100',
    });

    await verifyRegistrationLinkCreated(page, customerData);
  });
});
