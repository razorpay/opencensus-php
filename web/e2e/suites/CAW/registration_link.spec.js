import { getStorageStatePath, BASE_PATH, routes } from 'testConstants';
import { test } from 'utils/base';

import {
  fillCustomerDetails,
  fillPaymentDetails,
  fillTokenDetails,
  verifyRegistrationLinkCreated,
} from './utils';
import { navigateToInCurlecDashboard } from '../../utils/common';

test.describe.parallel('Create Registration Link @flow=CAW @country=MY', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).CURLEC_TEST_CAW_LOGIN_STATE,
  });

  test('should create a registration link with TNG as payment method', async ({ page }) => {
    await navigateToInCurlecDashboard(page, routes.NEW_REGISTRATION_LINKS);

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
      mandateMaxAmount: '100',
    });

    await verifyRegistrationLinkCreated(page, customerData);
  });
});
