const { expect } = require('@playwright/test');

export const loginByMobile = async ({ page, mobile }) => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', mobile);
  await page.route('**/user/signin/otp', async (route, request) => {
    console.log('SMS mock request intercepted');
    if (request.postData) {
      const existingBody = await request.postData();
      const requestBody = JSON.parse(existingBody);

      // add sms mock flag
      requestBody.skip_sms_request = true;

      const newRequestBody = JSON.stringify(requestBody);

      route.continue({ postData: newRequestBody });
    } else {
      route.continue();
    }
  });
  await page.click('text="Next"');
  await page.click('input[id="Enter OTP"]');
  await page.fill('input[id="Enter OTP"]', '000007');
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

export const loginByEmail = async ({ page, cred }) => {
  await page.click('input[type="text"]');
  console.log('cred.username', cred.username);
  await page.fill('input[type="text"]', cred.username);
  await page.click('text="Next"');
  await page.click('input[type="password"]');
  await page.fill('input[type="password"]', cred.password);
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
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

export const hideSearchFTUXBannerByLocalStorage = async ({ page }) => {
  await page.addInitScript(() => {
    window.localStorage.setItem(
      'universal-search-ftux',
      JSON.stringify({
        count: 3,
        expireAt: '2023-05-12T15:25:27+05:30',
      }),
    );
  });
};

export const saveTestEnvironment = async ({ page, cookieItems = [], localStorageItems = {} }) => {
  const combinedLocalStorageItems = {
    ...localStorageItems,
    regressionEnv: 'playwright',
    itfLabel: process.env.DEVSTACK_LABEL,
    baseUrl: process.env.E2E_BASE_URL,
  };

  const combinedCookieItems = [...cookieItems];

  await page.evaluate((storageItems) => {
    Object.keys(storageItems).forEach((key) => {
      localStorage.setItem(key, storageItems[key]);
    });
  }, combinedLocalStorageItems);

  await page.context().addCookies(combinedCookieItems);
};

const getTestModeStoragePage = (path) => path.replace('.json', '-test-mode.json');

export const saveTestModeCredentials = async ({ page, cred }) => {
  let retry = 3;

  while (retry > 0) {
    try {
      const modeSwitchToggle = page.locator('a.switch-modes-toggle');
      await expect(modeSwitchToggle).toBeVisible();
      await modeSwitchToggle.click();
      const testModeOption = await page.locator('li[data-test="Test Mode"] > a');
      await expect(testModeOption).toBeVisible();
      await testModeOption.dispatchEvent('click');
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
};

// ================================
//  Utils for Success Rate
//  Please also modify in libs/shared-utils/src/e2e/utils/common.js
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

  const metricName = 'merchant.dashboard.e2e.status';
  const srData = formatDataForSR(testInfo);
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
