import { expect } from './base';
import { COMMON_SELECTORS } from './selectors';
import { Page, Locator, Cookie } from '@playwright/test';
import { ExtendedPage } from './base';

/** Reusable interfaces for common parameters */
interface PageParams {
  page: ExtendedPage;
}

interface WaitForSelectorParams extends PageParams {
  selector: string;
  options?: Parameters<Page['waitForSelector']>[1];
}

interface LoginByMobileParams extends PageParams {
  mobile: string;
}

interface LoginByEmailParams extends PageParams {
  cred: {
    username: string;
    password: string;
  };
}

interface SaveTestEnvironmentParams extends PageParams {
  cookieItems?: Cookie[];
  localStorageItems?: Record<string, string | undefined>;
}

interface SaveTestModeCredentialsParams extends PageParams {
  cred: { storagePath: string };
}

interface MouseClickToggleSwitchParams {
  page: Page;
  container?: Locator | Page;
}

/** Navigation functions */
export async function navigateTo({ page }: PageParams, path: string): Promise<void> {
  await page.goto(path);
  await expect(page).toHaveTitle(/Razorpay Dashboard/);
}

export async function navigateToInCurlecDashboard(
  { page }: PageParams,
  path: string,
): Promise<void> {
  await page.goto(path);
  await expect(page).toHaveTitle(/Curlec By Razorpay/);
}

/** Login functions */
export const loginByMobile = async ({ page, mobile }: LoginByMobileParams): Promise<void> => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', mobile);
  await page.click('text="Next"');
  await page.click('input[id="Enter OTP"]');
  await page.fill('input[id="Enter OTP"]', '000007');
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

export const loginByEmail = async ({ page, cred }: LoginByEmailParams): Promise<void> => {
  await page.click('input[type="text"]');
  await page.fill('input[type="text"]', cred.username);
  await page.click('text="Next"');
  await page.click('input[type="password"]');
  await page.fill('input[type="password"]', cred.password);
  await Promise.all([page.waitForNavigation(), page.click('text="Login"')]);
};

/** Utility functions */
export const wait = (ms: number): Promise<void> => new Promise((res) => setTimeout(res, ms));

export const mouseClickToggleSwitch = async ({
  page,
  container = page,
}: MouseClickToggleSwitchParams): Promise<void> => {
  await page.waitForTimeout(5000);
  const button = await container.locator(COMMON_SELECTORS.toggleSwitch);
  const boundingBox = await button.boundingBox();
  if (boundingBox) {
    const { x, y } = boundingBox;
    await page.mouse.click(x + 10, y + 10, { button: 'left', clickCount: 1 });
  }
};

/** Local storage manipulation */
export const hideCustomBannersFromState = async ({ page }: PageParams): Promise<void> => {
  await page.evaluate(() => {
    const merchantId = window?.rzp_user?.current;
    if (merchantId) {
      window.localStorage.setItem(`NOT_INTERESTED-${merchantId}`, '1');
    }
  });
};

export const hideCustomerGluGame = async ({ page }: PageParams): Promise<void> => {
  await page.evaluate(() => {
    window.localStorage.setItem(`CUSTOMER_GLU_E2E`, 'off');
  });
};

export const showStreakRewardTileInAccountPage = async ({ page }: PageParams): Promise<void> => {
  await page.evaluate(() => {
    window.localStorage.setItem(`CUSTOMER_GLU_URL_E2E`, 'on');
  });
};

/** Switching merchants */
export const switchMerchant = async ({
  page,
  merchantToSwitch,
}: PageParams & { merchantToSwitch: string }): Promise<void> => {
  const switchMerchantCta = await page.locator(COMMON_SELECTORS.switchMerchantAction, {
    hasText: 'Switch Merchant',
  });
  await switchMerchantCta.click();

  const selectAndRedirectAction = await page.locator(COMMON_SELECTORS.merchantDropdownList, {
    hasText: merchantToSwitch,
  });
  await selectAndRedirectAction.click();
};

/** Waiting for selectors */
export const waitForSelectorToBeVisible = async ({
  page,
  selector,
  options = {},
}: WaitForSelectorParams): Promise<void> => {
  await page.waitForSelector(selector, options);
  const locator = await page.locator(selector);
  await expect(locator).toBeVisible();
};

/** Logging to console */
export const pageConsoleLog = async (page: Page, ...args: any[]): Promise<void> => {
  await page.evaluate((...evaluatedArgs: any[]) => {
    console.log(...evaluatedArgs);
  }, ...args);
};

/** Saving environment */
export const saveTestEnvironment = async ({
  page,
  cookieItems = [],
  localStorageItems = {},
}: SaveTestEnvironmentParams): Promise<void> => {
  const cleanedLocalStorageItems = Object.fromEntries(
    Object.entries(localStorageItems).filter(([_, value]) => value !== undefined),
  );

  const combinedLocalStorageItems = {
    ...cleanedLocalStorageItems,
    regressionEnv: 'playwright',
    itfLabel: process.env.DEVSTACK_LABEL || '',
    baseUrl: process.env.E2E_BASE_URL || '',
  };

  await page.evaluate((storageItems) => {
    Object.keys(storageItems).forEach((key) => {
      // @ts-ignore
      localStorage.setItem(key, storageItems[key]);
    });
  }, combinedLocalStorageItems);

  await page.context().addCookies(cookieItems);
};

/** Saving test mode credentials */
export const saveTestModeCredentials = async ({
  page,
  cred,
}: SaveTestModeCredentialsParams): Promise<void> => {
  let retry = 3;
  while (retry > 0) {
    try {
      const modeSwitchToggle: Locator = page.locator('a.switch-modes-toggle');
      await expect(modeSwitchToggle).toBeVisible();
      await modeSwitchToggle.click();
      const testModeOption: Locator = page.locator('li[data-test="Test Mode"] > a');
      await expect(testModeOption).toBeVisible();
      await testModeOption.dispatchEvent('click');
      await page.waitForSelector("text=/YOU'RE IN TEST MODE/i");
      await page.context().storageState({
        path: getTestModeStoragePage(cred.storagePath),
      });
      return;
    } catch {
      retry--;
    }
  }
  throw new Error('Failed to switch to test mode');
};

/** Utility for storage paths */
export const getTestModeStoragePage = (path: string): string =>
  path.replace('.json', '-test-mode.json');
