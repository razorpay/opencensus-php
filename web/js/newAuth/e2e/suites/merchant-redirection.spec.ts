import {
  routes,
  playwrightTest,
  expect,
  loginByEmail,
  playwrightEnvs as ENV,
} from '@libs/shared-qsuite/playwright';

const EASY_ONBOARDING_WEBSITE = 'https://sme-dashboard.dev.razorpay.in/';
const UNIFIED_ONBOARDING_BASE_URL = 'https://accounts.np.razorpay.in';

const constants = {
  EASY_ONBOARDING: {
    username: ENV.EASY_ONBOARDING_USERNAME,
    password: ENV.EASY_ONBOARDING_PASSWORD,
    merchantId: ENV.EASY_ONBOARDING_MERCHANT_ID,
    rzpUserId: ENV.EASY_ONBOARDING_RZP_USER_ID,
  },
  EASY_ONBOARDING_FTUX: {
    username: ENV.EASY_ONBOARDING_FTUX_USERNAME,
    password: ENV.EASY_ONBOARDING_FTUX_PASSWORD,
  },
  EASY_ONBOARDING_P2PM: {
    username: ENV.EASY_ONBOARDING_P2PM_USERNAME,
    password: ENV.EASY_ONBOARDING_P2PM_PASSWORD,
  },
  SIGNUP_REDIRECTION_URL: `${EASY_ONBOARDING_WEBSITE}onboarding?source=website`,
  FTUX_REDIRECTION_URL: `${EASY_ONBOARDING_WEBSITE}onboarding/overview`,
  P2PM_REDIRECTION_URL: `${EASY_ONBOARDING_WEBSITE}onboarding/p2pm`,
  UNIFIED_SIGNUP_REDIRECTION_URL: `${UNIFIED_ONBOARDING_BASE_URL}/auth/?redirecturl=https%3A%2F%2Fsme-dashboard.dev.razorpay.in&auth_intent=signup`,
};

playwrightTest.describe.parallel(
  'Dashboard Redirection flow @flow=auth',
  () => {
    playwrightTest(
      'should redirect the user to easy dashboard on clicking signup',
      async ({ page }) => {
        await page.goto(routes.SIGN_IN_PATH, { useOriginal: true });
        const signUpButton = page.getByRole('button', { name: 'Sign Up' });
        await signUpButton.waitFor({ state: 'visible' });
        await signUpButton.click();

        const promises = [
          expect(page).toHaveURL(constants.UNIFIED_SIGNUP_REDIRECTION_URL, { timeout: 10000 }),
          expect(page).toHaveURL(constants.SIGNUP_REDIRECTION_URL, { timeout: 10000 }),
        ];

        const results = await Promise.allSettled(promises);

        const fulfilledResults = results.filter((result) => result.status === 'fulfilled');

        if (fulfilledResults.length > 0) {
          console.log('one of the two redirection passed');
        } else {
          console.error(results);
          throw new Error('Redirection failed');
        }
      },
    );

    playwrightTest.skip(
      'should redirect the user to easy dashboard if user has initiated signup on easy',
      async ({ page }) => {
        await page.goto(routes.SIGN_IN_PATH, { useOriginal: true });
        const redirectionRequest = page.waitForResponse('/app/dashboard');
        await loginByEmail({ page, cred: constants.EASY_ONBOARDING });
        const signInResponse = await redirectionRequest;
        const setCookieHeaderValue = await signInResponse.headerValue('set-cookie');
        // redirection to easy dashboard should have merchant id and user id
        expect(setCookieHeaderValue).toContain(
          `rzp_merchant_id=${constants.EASY_ONBOARDING.merchantId}`,
        );
        expect(setCookieHeaderValue).toContain(
          `rzp_user_id=${constants.EASY_ONBOARDING.rzpUserId}`,
        );
        await page.waitForURL(EASY_ONBOARDING_WEBSITE, { waitUntil: 'load' });
        await expect(page).toHaveURL(EASY_ONBOARDING_WEBSITE);
      },
    );

    playwrightTest.skip(
      "should redirect the FTUX user to easy dashboard's FTUX experience on logging in",
      async ({ page }) => {
        await page.goto(routes.SIGN_IN_PATH, { useOriginal: true });

        await loginByEmail({ page, cred: constants.EASY_ONBOARDING_FTUX });
        await expect(page).toHaveURL(constants.FTUX_REDIRECTION_URL);
        await page.waitForURL(constants.FTUX_REDIRECTION_URL, { waitUntil: 'load' });
      },
    );

    playwrightTest(
      "should redirect the P2PM onboarding user to easy dashboard's P2PM experience on logging in",
      async ({ page }) => {
        await page.goto(routes.SIGN_IN_PATH, { useOriginal: true });

        await loginByEmail({ page, cred: constants.EASY_ONBOARDING_P2PM });
        await expect(page).toHaveURL(constants.P2PM_REDIRECTION_URL);
        await page.waitForURL(constants.P2PM_REDIRECTION_URL, { waitUntil: 'load' });
      },
    );
  },
);
