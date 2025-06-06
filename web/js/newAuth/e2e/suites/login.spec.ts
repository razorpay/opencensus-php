import {
  hideSearchFTUXBannerByLocalStorage,
  hideCustomBannersFromState,
  hideCustomerGluGame,
  loginUsl,
  showStreakRewardTileInAccountPage,
  saveTestEnvironment,
  saveTestModeCredentials,
  routes,
  getCredentials,
  playwrightTest,
  expect,
  setUserInteractedWithHomeConsent,
} from '@libs/shared-qsuite/playwright';

playwrightTest.beforeEach(async ({ context, page }) => {
  await hideSearchFTUXBannerByLocalStorage({ page });
  await setUserInteractedWithHomeConsent({ page });
  await page.goto(routes.SIGN_IN_PATH);
  await expect(page).toHaveTitle(/Razorpay Accounts/);
});
playwrightTest.describe.parallel('Dashboard login flow @flow=auth @package=others', () => {
  const { emailCred, activatedNotIe, mobileCred, posCredentials } = getCredentials();
  // testing for multiple credentials using email login
  for (const cred of [...emailCred, ...activatedNotIe, ...posCredentials]) {
    playwrightTest(
      `should login with email in ${cred.type} mode: @priority=critical @duration=long`,
      async ({ page }) => {
        // applying login form with email and password
        await loginUsl({
          page,
          type: 'email',
          username: cred.username,
          password: cred.password,
          multiAccountMerchantName: cred.multiAccountMerchantName,
        });
        await expect(page).toHaveTitle(/Razorpay Dashboard/, {
          timeout: 2 * 60 * 1000,
        });

        // hiding custom banner popups by udating local storage
        await hideCustomBannersFromState({ page });
        // avoid loading customer glu Game script by updating local storage
        await hideCustomerGluGame({ page });
        // show streak reward tiles in account page for e2e based in localStorage instead of experiment evaluation
        await showStreakRewardTileInAccountPage({ page });

        // saving playwrightTest environment in browser context
        await saveTestEnvironment({ page });

        // storing login state in context to re-use at other logins.
        await page.context().storageState({
          path: cred.storagePath,
        });

        if (cred.hasTestMode) {
          await saveTestModeCredentials({ page, cred });
        }
      },
    );
  }

  // testing mobile-otp login
  for (const cred of mobileCred) {
    // TODO: Tests need to be updated
    playwrightTest(
      `should login with mobile in ${cred.type} mode: @priority=critical @duration=long`,
      async ({ page }) => {
        // applying login form with mobile and otp
        await loginUsl({
          page,
          type: 'mobile',
          mobile: cred.mobile,
          multiAccountMerchantName: cred.multiAccountMerchantName,
        });

        await expect(page).toHaveTitle(/Razorpay Dashboard/, {
          timeout: 2 * 60 * 1000,
        });

        // saving playwrightTest environment in browser context
        await saveTestEnvironment({ page });

        // storing login state in context to re-use at other logins
        await page.context().storageState({
          path: cred.storagePath,
        });

        // @ts-ignore
        if (cred.hasTestMode) {
          await saveTestModeCredentials({ page, cred });
        }
      },
    );
  }
});
