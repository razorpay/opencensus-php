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

test.beforeEach(async ({ page }, testInfo) => {
  await page.goto(routes.DASHBOARD);
  await setTestConfigInLocalStorageForAnalytics(page, testInfo);
});

module.exports = { test, expect };
