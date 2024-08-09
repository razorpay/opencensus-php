import {
  hideSearchFTUXBannerByLocalStorage,
  hideCustomBannersFromState,
  hideCustomerGluGame,
  loginByEmail,
  loginByMobile,
  showStreakRewardTileInAccountPage,
  saveTestEnvironment,
} from '../utils';

import { routes } from '../../constants/constants';

const { test, expect } = require('@playwright/test');

const { getCredentials } = require('../../utils/config');

const getTestModeStoragePage = (path) => path.replace('.json', '-test-mode.json');

test.describe.parallel('Dashboard login flow @flow=auth @package=others', () => {
  const { emailCred, activatedNotIe, mobileCred, posCredentials } = getCredentials();
  // testing for multiple credentials using email login
  for (const cred of emailCred) {
    test(`should login with email in ${cred.type} mode: @priority=critical @duration=long`, async ({
      page,
    }) => {
      await hideSearchFTUXBannerByLocalStorage({ page });
      await page.goto(routes.SIGN_IN_PATH);
      await expect(page).toHaveTitle(/Razorpay Dashboard/);

      // applying login form with email and password
      await loginByEmail({
        page,
        cred,
      });

      // validating landing page url after login
      await expect(page).toHaveURL(routes.DASHBOARD);

      // hiding custom banner popups by udating local storage
      await hideCustomBannersFromState({ page });
      // avoid loading customer glu Game script by updating local storage
      await hideCustomerGluGame({ page });
      // show streak reward tiles in account page for e2e based in localStorage instead of experiment evaluation
      await showStreakRewardTileInAccountPage({ page });

      // saving test environment in browser context
      await saveTestEnvironment({ page });

      // storing login state in context to re-use at other logins
      await page.context().storageState({
        path: cred.storagePath,
      });

      if (cred.hasTestMode) {
        let retry = 3;

        while (retry > 0) {
          try {
            const modeSwitchToggle = page.locator('a.switch-modes-toggle');
            await expect(modeSwitchToggle).toBeVisible();
            await modeSwitchToggle.click();
            await page.locator('li[data-test="Test Mode"]').click();
            await page.waitForSelector("text=/YOU'RE IN TEST MODE/i");
            await page.context().storageState({
              path: getTestModeStoragePage(cred.storagePath),
            });
            return;
          } catch (error) {
            retry--;
          }
        }
        throw new Error('Failed to switch to test mode');
      }
    });
  }

  // testing mobile-otp login
  for (const cred of mobileCred) {
    // TODO: Tests need to be updated
    test(`should login with mobile in ${cred.type} mode: @priority=critical @duration=long`, async ({
      page,
    }) => {
      await hideSearchFTUXBannerByLocalStorage({ page });
      await page.goto(routes.SIGN_IN_PATH);
      await expect(page).toHaveTitle(/Razorpay Dashboard/);

      // applying login form with mobile and otp
      await loginByMobile({
        page,
        mobile: cred.mobile,
      });

      // validating landing page url after login
      await expect(page).toHaveURL(routes.DASHBOARD);

      // saving test environment in browser context
      await saveTestEnvironment({ page });

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
      await loginByEmail({
        page,
        cred,
      });

      // validating landing page url after login
      await expect(page).toHaveURL(routes.DASHBOARD);

      // hiding custom banner popups by udating local storage
      await hideCustomBannersFromState({ page });
      // avoid loading customer glu Game script by updating local storage
      await hideCustomerGluGame({ page });
      // show streak reward tiles in account page for e2e based in localStorage instead of experiment evaluation
      await showStreakRewardTileInAccountPage({ page });

      // saving test environment in browser context
      await saveTestEnvironment({ page });

      // storing login state in context to re-use at other logins
      await page.context().storageState({
        path: cred.storagePath,
      });
    });
  }

  for (const cred of posCredentials) {
    test(`should login with email in ${cred.type} mode: @priority=critical @duration=long`, async ({
      page,
    }) => {
      await hideSearchFTUXBannerByLocalStorage({ page });
      await page.goto(routes.SIGN_IN_PATH);
      await expect(page).toHaveTitle(/Razorpay Dashboard/);

      // applying login form with email and password
      await loginByEmail({
        page,
        cred,
      });

      // validating landing page url after login
      await expect(page).toHaveURL(routes.DASHBOARD);

      // hiding custom banner popups by udating local storage
      await hideCustomBannersFromState({ page });

      // saving test environment in browser context
      await saveTestEnvironment({ page });

      // storing login state in context to re-use at other logins
      await page.context().storageState({
        path: cred.storagePath,
      });
    });
  }
});
