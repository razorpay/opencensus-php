import { expect } from './base';

import { COMMON_SELECTORS } from './selectors';

export async function navigateTo(page, path) {
  await page.goto(path);
  await expect(page).toHaveTitle(/Razorpay Dashboard/);
}

export async function navigateToInCurlecDashboard(page, path) {
  await page.goto(path);
  await expect(page).toHaveTitle(/Curlec By Razorpay/);
}

export const loginByMobile = async ({ page, mobile }) => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', mobile);
  await page.click('text="Next"');
  await page.click('input[id="Enter OTP"]');
  await page.fill('input[id="Enter OTP"]', '000007');
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

export const loginByEmail = async ({ page, cred }) => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', cred.username);
  await page.click('text="Next"');
  await page.click('input[type="password"]');
  await page.fill('input[type="password"]', cred.password);
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

export const wait = (ms) => new Promise((res) => setTimeout(() => res(), ms));

// the toggle switch web/js/common/ui/Forms/SwitchField.js expects the
// on click event to have some page x and page y movement, therefore need to stimulate it
export const mouseClickToggleSwitch = async ({ page, container = page }) => {
  await page.waitForTimeout(5000);
  const button = await container.locator(COMMON_SELECTORS.toggleSwitch);
  // switch knob is expecting some value for event.pageX and event.pageY, therefore stimulating mouse movement
  const { x, y } = await button.boundingBox();
  await page.mouse.click(x + 10, y + 10, { button: 'left', clickCount: 1 });
};

export const hideCustomBannersFromState = async ({ page }) => {
  await page.evaluate(() => {
    const merchantId = window?.rzp_user?.current;
    if (merchantId) {
      window.localStorage.setItem(`NOT_INTERESTED-${merchantId}`, '1');
    }
  });
};

export const hideCustomerGluGame = async ({ page }) => {
  await page.evaluate(() => {
    window.localStorage.setItem(`CUSTOMER_GLU_E2E`, 'off');
  });
};
export const showStreakRewardTileInAccountPage = async ({ page }) => {
  await page.evaluate(() => {
    window.localStorage.setItem(`CUSTOMER_GLU_URL_E2E`, 'on');
  });
};

export const switchMerchant = async ({ page, merchantToSwitch }) => {
  const switchMerchantCta = await page.locator(COMMON_SELECTORS.switchMerchantAction, {
    hasText: 'Switch Merchant',
  });
  await switchMerchantCta.click();

  const SelectAndRedirectAction = await page.locator(COMMON_SELECTORS.merchantDropdownList, {
    hasText: merchantToSwitch,
  });
  await SelectAndRedirectAction.click();
};

export const waitForSelectorToBeVisible = async ({ page, selector }, options) => {
  await page.waitForSelector(selector, options);
  const locator = await page.locator(selector);
  await expect(locator).toBeVisible();
};

export const pageConsoleLog = async (page, ...args) => {
  await page.evaluate((args) => {
    console.log(...args);
  }, args);
};
