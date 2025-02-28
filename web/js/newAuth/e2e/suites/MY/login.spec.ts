import {
  routes,
  loginByEmail,
  saveTestEnvironment,
  saveTestModeCredentials,
  playwrightTest,
  getCredentials,
} from '@libs/shared-qsuite/playwright';

playwrightTest.describe.parallel('Dashboard login flow @flow=MY-auth @country=MY', () => {
  // testing for multiple credentials using email login
  const { curlecCred } = getCredentials();
  for (const cred of curlecCred) {
    // Undo before merge
    playwrightTest.skip(
      `should login with curlec email in ${cred.type} mode: @priority=critical @duration=long`,
      async ({ page }) => {
        await page.goto(routes.SIGN_IN_PATH);

        // applying login form with email and password
        await loginByEmail({
          page,
          cred,
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
