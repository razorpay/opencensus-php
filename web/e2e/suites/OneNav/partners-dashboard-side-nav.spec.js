import { routes, test, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { tabOpenInSameTab } from './utils';

test.describe.parallel('One nav partners dashboard side nav tabs @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_2,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.PARTNER_DASHBOARD);
    // Slack thread: https://razorpay.slack.com/archives/C06F9MYVBR7/p1751372364744419?thread_ts=1751355410.206079&cid=C06F9MYVBR7
    await page
      .locator('.main-content--one-dashboard, .main-content--connected-navigation')
      .waitFor({
        state: 'visible',
        timeout: 60000,
      });
  });

  test(`should navigate to affiliate accounts`, async ({ page }) => {
    await page.getByRole('link', { name: 'Affiliate Accounts' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/partners/submerchants',
      expectedTextRegex: 'Add new clients',
    });
  });

  test(`should navigate to partner playbook`, async ({ page }) => {
    await page.getByRole('link', { name: 'Partner Playbook' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/partners/playbook',
      expectedTextRegex: 'Razorpay partner',
    });
  });

  test(`should navigate to earnings`, async ({ page }) => {
    await page.getByRole('link', { name: 'Earnings' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/partners/earnings/daily',
      expectedTextRegex: 'Total Earnings',
    });
  });

  test(`should navigate to applications`, async ({ page }) => {
    await page.getByRole('link', { name: 'Applications' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/partners/applications',
      expectedTextRegex: 'Created Applications',
    });
  });

  test(`should navigate to report pages`, async ({ page }) => {
    await page.getByRole('link', { name: 'Reports' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/partners/reports',
      expectedTextRegex: 'Generate & schedule reports',
    });
  });
});
