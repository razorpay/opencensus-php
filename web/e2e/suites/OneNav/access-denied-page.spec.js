import { routes, test, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { tabOpenInSameTab } from './utils';

test.describe.parallel('One nav dashboards access denied page @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_2,
  });

  // No access denied page on payments dashboard
  // test(`should show access denied page on Payments dashboard`, async ({ page }) => {
  //   await page.goto(routes.PARTNER_DASHBOARD);
  //   await tabOpenInSameTab({
  //     page,
  //     expectedUrl: routes.DASHBOARD,
  //     expectedTextRegex: 'You do not have access to payments',
  //   });
  // });

  test(`should show access denied page on Banking dashboard`, async ({ page }) => {
    await page.goto(routes.BANKING);
    await tabOpenInSameTab({
      page,
      expectedUrl: routes.BANKING,
      expectedTextRegex: 'You do not have access to Banking+',
    });
  });
});
