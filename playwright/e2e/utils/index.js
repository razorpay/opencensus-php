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
