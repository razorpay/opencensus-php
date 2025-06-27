import {
  routes,
  test,
  expect,
  getStorageStatePath,
  setUserInteractedWithHomeConsent,
} from '@libs/shared-qsuite/playwright';
import { tabOpenInSameTab, waitForOneDashboard } from './utils';

const homePageText = 'Razorpay Home';
const paymentsPageText = 'Payments';

test.describe.parallel('One nav dashboard routing for oneHome enabled @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
  });

  test('should redirect to OneHome when an unregistered random route is opened', async ({
    page,
  }) => {
    await page.goto(routes.RANDOM_ROUTE);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.HOME,
      expectedTextRegex: homePageText,
    });
  });

  test('should redirect to OneHome when root path is opened', async ({ page }) => {
    await page.goto(routes.ROOT_PATH);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.HOME,
      expectedTextRegex: homePageText,
    });
  });

  test('should redirect to payments home when payments dashboard is opened and consent is accepted', async ({
    page,
  }) => {
    await page.goto(routes.DASHBOARD);
    await waitForOneDashboard(page);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.DASHBOARD,
      expectedTextRegex: paymentsPageText,
    });
  });

  test('should redirect to OneHome when payments dashboard is opened and consent is not accepted', async ({
    page,
  }) => {
    await page.evaluate(() => {
      window.localStorage.removeItem('hasUserInteractedWithHomeConsent');
      window.localStorage.removeItem('isOneHomeConsentAccepted');
    });
    await page.goto(routes.DASHBOARD);
    await page.reload();
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.HOME,
      expectedTextRegex: homePageText,
    });
  });
});

test.describe.parallel('One nav dashboard routing for oneHome disabled @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_5,
  });

  test('should redirect to payments home when an unregistered random route is opened', async ({
    page,
  }) => {
    await page.goto(routes.RANDOM_ROUTE);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.DASHBOARD,
      expectedTextRegex: paymentsPageText,
    });
  });

  test('should redirect to payments home when root path is opened', async ({ page }) => {
    await page.goto(routes.ROOT_PATH);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.DASHBOARD,
      expectedTextRegex: paymentsPageText,
    });
  });

  test('should redirect to payments home when connected home is opened', async ({ page }) => {
    await page.goto(routes.HOME);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.DASHBOARD,
      expectedTextRegex: paymentsPageText,
    });
  });

  test('should go to the page specified when dashboard is opened and consent is not accepted', async ({
    page,
  }) => {
    await page.evaluate(() => {
      window.localStorage.removeItem('hasUserInteractedWithHomeConsent');
      window.localStorage.removeItem('isOneHomeConsentAccepted');
    });
    await page.goto(routes.DASHBOARD);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.DASHBOARD,
      expectedTextRegex: paymentsPageText,
    });
  });
});

test.describe
  .parallel('One nav dashboard routing for oneHome disabled - Mobile @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_5,
    viewport: {
      width: 480,
      height: 568,
    },
    deviceScaleFactor: 2,
  });

  test('should redirect to payments home when connectedhome is opened', async ({ page }) => {
    await page.goto(routes.HOME);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.HOME,
      expectedTextRegex: homePageText,
    });
  });
});
