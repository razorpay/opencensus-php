const { test, expect } = require('@playwright/test');
const { routes } = require('testConstants');

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
      if (options.useOriginal) {
        return originalGoto(url, options);
      }
      return await page.evaluate(async (url) => {
        window.history.pushState({}, undefined, url);
        dispatchEvent(new PopStateEvent('popstate', {}));
      }, url);
    };

    await use(page);
  },
});

module.exports = { test: testExtended, expect };
