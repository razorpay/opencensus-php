const { test, expect } = require('@playwright/test');
const { routes } = require('testConstants');
const { pushSRData } = require('playwright/e2e/utils');

// Utility function to set a value in localStorage
async function setTestConfigInLocalStorageForAnalytics(page, testInfo) {
  const {
    title,
    // config: { projects },
  } = testInfo;
  const info = {
    testName: title,
  };

  await page.evaluate((storageInfo) => {
    Object.keys(storageInfo).forEach((key) => {
      localStorage.setItem(key, storageInfo[key]);
    });
  }, info);
}

function extractDevstackLabel(url) {
  // Regular expression pattern to extract ITF label
  const pattern = /itf[\w\d]+/;

  const match = url.match(pattern);

  return match ? match[0] : '';
}

const testExtended = test.extend({
  page: async ({ page }, use, testInfo) => {
    await page.goto(routes.DASHBOARD);
    await setTestConfigInLocalStorageForAnalytics(page, testInfo);

    /**
     * Override definition of page.goto
     * However, if a test wants to use original page.goto. It can pass { useOriginal: true } in options
     * Example : page.goto(url, { useOriginal: true });
     * In absence of useOriginal, it will use the overridden definition, which directly pushes url to history object
     */
    const originalGoto = page.goto.bind(page);

    page.goto = async (url, options = {}) => {
      if (options?.useOriginal) {
        return originalGoto(url, options);
      }
      const domain = await page.evaluate((url) => {
        window.history.pushState({}, undefined, url);
        dispatchEvent(new PopStateEvent('popstate', {}));
        return window.location.origin;
      }, url);

      const devstackLabel = extractDevstackLabel(domain);
      const grafanaUrl = `https://grafana.np.razorpay.in/d/fffac27f-2f8f-477b-879c-0efbee34b653/merchant-dashboard-devstack?orgId=1&var-namespace=All&var-deployment=All&var-devstack_label=${devstackLabel}&from=now-2d&to=now`;
      console.log(`Devstack label: ${devstackLabel}`);
      console.log(`Grafana URL: ${grafanaUrl}`);
    };

    await use(page);
    await pushSRData({ testInfo });
  },
});

module.exports = { test: testExtended, expect };
