import { loginByEmail } from '../../../utils/common';
import { routes } from '../../../utils/constants';

const { test } = require('@playwright/test');

const { getCredentials } = require('../../../utils/config');

const { curlecCred } = getCredentials();

test.describe.parallel('Dashboard login flow @flow=MY-auth @country=MY', () => {
  // testing for multiple credentials using email login
  for (const cred of curlecCred) {
    test(`should login with curlec email in ${cred.type} mode: @priority=critical @duration=long`, async ({
      page,
    }) => {
      await page.goto(routes.SIGN_IN_PATH);

      // applying login form with email and password
      await loginByEmail({ page, cred });

      // storing login state in context to re-use at other logins
      await page.context().storageState({
        path: cred.storagePath,
      });
    });
  }
});
