import { expect } from '@playwright/test';

import { COMMON_SELECTORS } from './selectors';

export async function navigateTo(page, path) {
  await page.goto(path);
  await expect(page).toHaveTitle(/Razorpay Dashboard/);
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
  await page.mouse.click(x + 10, y + 10, {
    button: 'left',
    clickCount: 1,
  });
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

// ================================
//  Utils for Success Rate
//  Please also modify in playwright/e2e/utils/index.js
// ================================
const removeTags = (str) => str.replace(/@.*$/i, '');

export const formatDataForSR = ({ file, titlePath, status }) => {
  const formattedTitle = titlePath
    .slice(1)
    .map(removeTags)
    .map((str) => str.trim())
    .join(' | ')
    .toLowerCase()
    .trim();
  const dataPoints = {
    title: formattedTitle,
    status: status === 'passed' ? 'passed' : 'failed',
    module: file.toLowerCase(),
  };
  return dataPoints;
};

export const pushSRData = async ({ testInfo }) => {
  const isCI = process.env.CI;
  const LJ_KEY = process.env.LUMBERJACK_KEY_PROD;

  const srData = formatDataForSR(testInfo);
  const metricName = 'merchant.dashboard.e2e.status';
  if (isCI) {
    // push to querybook
    const body = {
      mode: 'live',
      key: LJ_KEY,
      events: [
        {
          event_type: 'pg-dashboard',
          event: metricName,
          event_version: 'v1',
          timestamp: new Date().getTime(),
          properties: {
            ...srData,
          },
        },
      ],
    };

    await fetch('https://lumberjack.razorpay.com/v1/track', {
      method: 'post',
      body: JSON.stringify(body),
      headers: {
        'Content-Type': 'application/json',
      },
      keepalive: true,
    })
      .then(() => {
        console.log('Successfully pushed SR data');
      })
      .catch((e) => {
        console.log('Error in pushing SR data', e);
      });

    // push to grafana
    const myHeaders = new Headers();
    myHeaders.append('Accept', '*/*');
    myHeaders.append('Connection', 'keep-alive');
    myHeaders.append('Content-Type', 'application/json');
    const raw = JSON.stringify({
      key: LJ_KEY,
      metrics: [
        {
          name: metricName,
          labels: [srData],
        },
      ],
    });
    const requestOptions = {
      method: 'POST',
      headers: myHeaders,
      body: raw,
    };
    await fetch(
      'https://lumberjack-metrics.razorpay.com/v1/frontend-metrics',
      requestOptions,
    ).catch(() => {});
  } else {
    console.log('SR Metric', srData);
  }
};

// ================================
//  End of Utils for Success Rate
// ================================
