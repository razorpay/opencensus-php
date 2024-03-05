import { expect, test } from '@playwright/test';
import getEnv from 'playwright/utils/env';
import { routes } from 'testConstants';
import { loginByEmail } from 'utils/common';

const EASY_ONBOARDING_WEBSITE = 'https://sme-dashboard.dev.razorpay.in/';
const ENV = getEnv();
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
};

test.describe.parallel('Dashboard Redirection flow @flow=critical @project=payments', () => {
  test('should redirect the user to easy dashboard on clicking signup', async ({ page }) => {
    await page.goto(routes.SIGN_IN_PATH);
    const signUpButton = page.getByRole('button', { name: 'Sign Up' });
    await signUpButton.waitFor({ state: 'visible', timeout: 10000 });
    await signUpButton.click();
    await expect(page).toHaveURL(constants.SIGNUP_REDIRECTION_URL);
  });

  test.skip('should redirect the user to easy dashboard if user has initiated signup on easy', async ({
    page,
  }) => {
    await page.goto(routes.SIGN_IN_PATH);
    const redirectionRequest = page.waitForResponse('/app/dashboard', { timeout: 25000 });
    await loginByEmail({ page, cred: constants.EASY_ONBOARDING });
    const signInResponse = await redirectionRequest;
    const setCookieHeaderValue = await signInResponse.headerValue('set-cookie');
    // redirection to easy dashboard should have merchant id and user id
    expect(setCookieHeaderValue).toContain(
      `rzp_merchant_id=${constants.EASY_ONBOARDING.merchantId}`,
    );
    expect(setCookieHeaderValue).toContain(`rzp_user_id=${constants.EASY_ONBOARDING.rzpUserId}`);
    await page.waitForURL(EASY_ONBOARDING_WEBSITE, { waitUntil: 'load', timeout: 25000 });
    await expect(page).toHaveURL(EASY_ONBOARDING_WEBSITE);
  });

  test.skip("should redirect the FTUX user to easy dashboard's FTUX experience on logging in", async ({
    page,
  }) => {
    await page.goto(routes.SIGN_IN_PATH);

    await loginByEmail({ page, cred: constants.EASY_ONBOARDING_FTUX });
    await expect(page).toHaveURL(constants.FTUX_REDIRECTION_URL);
    await page.waitForURL(constants.FTUX_REDIRECTION_URL, { waitUntil: 'load', timeout: 25000 });
  });

  test("should redirect the P2PM onboarding user to easy dashboard's P2PM experience on logging in", async ({
    page,
  }) => {
    await page.goto(routes.SIGN_IN_PATH);

    await loginByEmail({ page, cred: constants.EASY_ONBOARDING_P2PM });
    await expect(page).toHaveURL(constants.P2PM_REDIRECTION_URL);
    await page.waitForURL(constants.P2PM_REDIRECTION_URL, { waitUntil: 'load', timeout: 25000 });
  });
});
