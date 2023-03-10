const { test, expect } = require('@playwright/test');
const { getCredentials } = require('../utils/config');

const SIGN_IN_PATH = '/?screen=sign_in';
const DASHBOARD_PATH = '/app/dashboard';

const loginByEmail = async ({ page, cred }) => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', cred.username);
  await page.click('text="Next"');
  await page.click('input[type="password"]');
  await page.fill('input[type="password"]', cred.password);
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

const loginByMobile = async ({ page, cred }) => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', cred.mobile);
  await page.click('text="Next"');
  await page.click('input[id="Enter OTP"]');
  await page.fill('input[id="Enter OTP"]', '000007');
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

test.describe.parallel('Dashboard login flow @flow=auth', () => {
  const { emailCred, mobileCred } = getCredentials();

  // testing for multiple credentials using email login
  for (const cred of emailCred) {
    test(`should login with email in ${cred.type} mode: @priority=critical @duration=long`, async ({
      page,
    }) => {
      await page.goto(SIGN_IN_PATH);
      await expect(page).toHaveTitle(/Razorpay Dashboard/);

      // applying login form with email and password
      await loginByEmail({ page, cred });

      // validating landing page url after login
      await expect(page).toHaveURL(DASHBOARD_PATH);

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
      await page.goto(SIGN_IN_PATH);
      await expect(page).toHaveTitle(/Razorpay Dashboard/);

      // applying login form with mobile and otp
      await loginByMobile({ page, cred });

      // validating landing page url after login
      await expect(page).toHaveURL(DASHBOARD_PATH);

      // storing login state in context to re-use at other logins
      await page.context().storageState({
        path: cred.storagePath,
      });
    });
  }
});
