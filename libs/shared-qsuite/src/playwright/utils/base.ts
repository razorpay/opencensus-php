import { test as playwrightTest, expect, Page, TestInfo, Response } from '@playwright/test';
import { routes } from '../constants';

// Utility function to set a value in localStorage
async function setTestConfigInLocalStorageForAnalytics(page: Page, testInfo: TestInfo) {
  const { title } = testInfo;
  const info: Record<string, string> = {
    testName: title,
  };

  await page.evaluate((storageInfo: Record<string, string>) => {
    Object.keys(storageInfo).forEach((key) => {
      localStorage.setItem(key, storageInfo[key]);
    });
  }, info);
}

function extractDevstackLabel(url: string): string {
  // Regular expression pattern to extract ITF label
  const pattern = /itf[\w\d]+/;

  const match = url.match(pattern);

  return match ? match[0] : '';
}

// ExtendedPage Type
type ExtendedPage = Omit<Page, 'goto'> & {
  goto: (
    url: string,
    options?: {
      /**
       * Override definition of page.goto
       * However, if a test wants to use original page.goto, it can pass { useOriginal: true } in options
       * Example: page.goto(url, { useOriginal: true });
       * In the absence of useOriginal, it will use the overridden definition, which directly pushes URL to the history object
       */
      useOriginal?: boolean;
    } & Parameters<Page['goto']>[1],
  ) => Promise<Response | null>;
};

// Extend the test with the new ExtendedPage type
const testExtended = playwrightTest.extend<{
  page: ExtendedPage;
}>({
  // @ts-ignore
  page: async ({ page }: { page: Page }, use: typeof playwrightTest.use, testInfo: TestInfo) => {
    await page.goto(routes.DASHBOARD);
    await setTestConfigInLocalStorageForAnalytics(page, testInfo);

    const originalGoto = page.goto.bind(page);

    // Override the goto method to match the ExtendedPage type
    (page as ExtendedPage).goto = async (
      url: string,
      options?: { useOriginal?: boolean } & Parameters<Page['goto']>[1],
    ): Promise<Response | null> => {
      if (options?.useOriginal) {
        return originalGoto(url, options);
      }

      // Custom logic for handling the URL change
      await page.evaluate((url: string) => {
        window.history.pushState({}, '', url);
        dispatchEvent(new PopStateEvent('popstate', {}));
      }, url);

      // Extract domain and generate Grafana URL
      const domain = await page.evaluate(() => window.location.origin);
      const devstackLabel = extractDevstackLabel(domain);
      return null; // Return null explicitly to match the expected return type
    };

    // Use the extended page in the test
    // @ts-ignore
    await use(page);
  },
});

export {
  testExtended as test,
  /**
   * @warning This is not to be used anywhere apart from Login e2es.
   */
  playwrightTest,
  expect,
  ExtendedPage,
};
