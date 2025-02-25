import {
  routes,
  test,
  getStorageStatePath,
  navigateToInCurlecDashboard,
} from '@libs/shared-qsuite/playwright';

import {
  fillCustomerDetails,
  fillPaymentDetails,
  fillTokenDetails,
  verifyRegistrationLinkCreated,
} from './utils';

test.describe.parallel('Create Registration Link @flow=CAW @country=MY', () => {
  test.use({
    storageState: getStorageStatePath().CURLEC_TEST_CAW_LOGIN_STATE,
  });

  test.skip('should create a registration link with TNG as payment method', async ({ page }) => {
    await navigateToInCurlecDashboard({ page }, routes.NEW_REGISTRATION_LINKS);

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
