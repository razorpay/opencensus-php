import {
  hideSearchFTUXBannerByLocalStorage,
  hideCustomBannersFromState,
  hideCustomerGluGame,
  loginByEmail,
  loginByMobile,
  showStreakRewardTileInAccountPage,
  saveTestEnvironment,
  saveTestModeCredentials,
  routes,
  getCredentials,
  playwrightTest,
  expect,
} from '@libs/shared-qsuite/playwright';

playwrightTest.beforeEach(async ({ context, page }) => {
  await context.addCookies([
    {
      name: 'skip_usl_redirection',
      value: 'true',
      domain: '.razorpay.in',
      path: '/',
      httpOnly: true,
      secure: true,
      sameSite: 'None',
    },
  ]);
  await hideSearchFTUXBannerByLocalStorage({ page });
  await page.goto(routes.SIGN_IN_PATH);
  await expect(page).toHaveTitle(/Razorpay Dashboard/);
});
playwrightTest.describe.parallel('Dashboard login flow @flow=auth @package=others', () => {
  const { emailCred, activatedNotIe, mobileCred, posCredentials } = getCredentials();
  // testing for multiple credentials using email login
  for (const cred of emailCred) {
    playwrightTest(
      `should login with email in ${cred.type} mode: @priority=critical @duration=long`,
      async ({ page }) => {
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
        await loginByMobile({
          page,
          mobile: cred.mobile,
        });

        // validating landing page url after login
        await expect(page).toHaveURL(routes.DASHBOARD);

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

  for (const cred of activatedNotIe) {
    playwrightTest(
      `should login with email in ${cred.type} mode: @priority=critical @duration=long`,
      async ({ page }) => {
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

        // saving playwrightTest environment in browser context
        await saveTestEnvironment({ page });

        // storing login state in context to re-use at other logins
        await page.context().storageState({
          path: cred.storagePath,
        });

        if (cred.hasTestMode) {
          await saveTestModeCredentials({ page, cred });
        }
      },
    );
  }

  for (const cred of posCredentials) {
    playwrightTest(
      `should login with email in ${cred.type} mode: @priority=critical @duration=long`,
      async ({ page }) => {
        // applying login form with email and password
        await loginByEmail({
          page,
          cred,
        });

        // validating landing page url after login
        await expect(page).toHaveURL(routes.DASHBOARD);

        // hiding custom banner popups by udating local storage
        await hideCustomBannersFromState({ page });

        // saving playwrightTest environment in browser context
        await saveTestEnvironment({ page });

        // storing login state in context to re-use at other logins
        await page.context().storageState({
          path: cred.storagePath,
        });

        if (cred.hasTestMode) {
          await saveTestModeCredentials({ page, cred });
        }
      },
    );
  }
});
