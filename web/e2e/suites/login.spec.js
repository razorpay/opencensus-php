import { hideSearchFTUXBannerByLocalStorage } from '../utils';
import { loginByMobile, loginByEmail } from '../utils/common';
import { routes } from '../utils/constants';
const { test, expect } = require('@playwright/test');
const { getCredentials } = require('../utils/config');

test.describe.parallel('Dashboard login flow @flow=auth', () => {
  const { emailCred, mobileCred, activatedNotIe } = getCredentials();

  // testing for multiple credentials using email login
  for (const cred of emailCred) {
    test(`should login with email in ${cred.type} mode: @priority=critical @duration=long`, async ({
      page,
    }) => {
      await hideSearchFTUXBannerByLocalStorage({ page });
      await page.goto(routes.SIGN_IN_PATH);
      await expect(page).toHaveTitle(/Razorpay Dashboard/);

      // applying login form with email and password
      await loginByEmail({ page, cred });

      // validating landing page url after login
      await expect(page).toHaveURL(routes.DASHBOARD);
      // storing login state in context to re-use at other logins
      await page.context().storageState({
        path: cred.storagePath,
      });
    });
  }

  // testing for multiple credentials using mobile login
  for (const cred of mobileCred) {
    test(`should login with mobile in ${cred.type} mode: @priority=critical @duration=long`, async ({
      page,
    }) => {
      await hideSearchFTUXBannerByLocalStorage({ page });
      await page.goto(routes.SIGN_IN_PATH);
      await expect(page).toHaveTitle(/Razorpay Dashboard/);

      // applying login form with mobile and otp
      await loginByMobile({ page, mobile: cred.mobile });

      // validating landing page url after login
      await expect(page).toHaveURL(routes.DASHBOARD);
      // storing login state in context to re-use at other logins
      await page.context().storageState({
        path: cred.storagePath,
      });
    });
  }

  for (const cred of activatedNotIe) {
    test(`should login with email in ${cred.type} mode: @priority=critical @duration=long`, async ({
      page,
    }) => {
      await hideSearchFTUXBannerByLocalStorage({ page });
      await page.goto(routes.SIGN_IN_PATH);
      await expect(page).toHaveTitle(/Razorpay Dashboard/);

      // applying login form with email and password
      await loginByEmail({ page, cred });

      // validating landing page url after login
      await expect(page).toHaveURL(routes.DASHBOARD);
      // storing login state in context to re-use at other logins
      await page.context().storageState({
        path: cred.storagePath,
      });
    });
  }
});
