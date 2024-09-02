import { routes } from '@dashboard/shared-utils/e2e/constants/paths';
// eslint-disable-next-line @typescript-eslint/no-var-requires
const { test, expect } = require('@playwright/test');

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

    page.goto = async (url, options:{useOriginal?: boolean} = {}) => {
      if (options?.useOriginal) {
        return originalGoto(url, options);
      }
      const result = await page.evaluate(async (url) => {
        window.history.pushState({}, '', url);
        dispatchEvent(new PopStateEvent('popstate', {}));
      }, url);
      return result;
    };

    await use(page);
  },
});

export { testExtended as test, expect };
