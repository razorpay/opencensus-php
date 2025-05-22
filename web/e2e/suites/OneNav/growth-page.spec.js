import { routes, test, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { tabOpenInSameTab } from './utils';

test.describe.parallel('One nav dashboards growth page @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_3,
  });

  // No growth page on payments dashboard
  // test(`should show growth page on Payments dashboard`, async ({ page }) => {
  //   await page.goto(routes.DASHBOARD);
  //   await tabOpenInSameTab({
  //     page,
  //     expectedUrl: routes.DASHBOARD,
  //     expectedTextRegex: 'Supercharge your business with Razorpay Payment Gateway',
  //   });
  // });

  test(`should show growth page on Banking dashboard`, async ({ page }) => {
    await page.goto(routes.BANKING);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.BANKING,
      expectedTextRegex: 'Business Banking supercharged for disruptors',
    });
  });
});
