import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';

const { test, expect } = require('utils/base');

test.describe.parallel('Payment Metrics @flow=payments-metrics @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.PAYMENT_METRICS);
    await expect(page).toHaveURL(routes.PAYMENT_METRICS);
  });

  test('should navigate to Payment Metrics Page on CTA click', async ({ page }) => {
    // click on Payment Metrics link in sidebar
    await page.goto(routes.PAYMENT_METRICS);
  });

  test('should render top 2 comparison section', async ({ page }) => {
    const overallCrComparison = page.locator(
      'text=The percentage of users who successfully complete a payment after initiating Razorpay Checkout as of today, yesterday and last-week-same-day',
    );
    await expect(overallCrComparison).toBeVisible();
    const categoryCrComparison = page.locator(
      'text=The percentage of users who successfully complete a payment after initiating Razorpay Checkout within your category as of today, yesterday and last-week-same-day',
    );
    await expect(categoryCrComparison).toBeVisible();
  });

  test.describe.parallel('All Graphs', () => {
    test('should render all the graphs section', async ({ page }) => {
      const overallCrGraph = page.locator(
        'text=The percentage of users who successfully complete a payment after initiating Razorpay Checkout as a trending line chart',
      );
      await expect(overallCrGraph).toBeVisible();

      const methodLevelCrGraph = page.locator('text=Method Level Conversion rate');
      await expect(methodLevelCrGraph).toBeVisible();

      const categoryLevelCrGraph = page.locator(
        'text=The percentage of users who successfully complete a payment after initiating Razorpay Checkout within your specific industry',
      );
      await expect(categoryLevelCrGraph).toBeVisible();

      const transactionCountCrGraph = page.locator('text=Method Level Transaction Count');
      await expect(transactionCountCrGraph).toBeVisible();

      const totalGmvCrGraph = page.locator('text=Total GMV in Lakhs');
      await expect(totalGmvCrGraph).toBeVisible();

      const methodTotalGmvCrGraph = page.locator('text=Total GMV for All Methods in Lakh');
      await expect(methodTotalGmvCrGraph).toBeVisible();
    });
  });

  test.describe.parallel('Time Filter', () => {
    test('should update time filter', async ({ page }) => {
      await page.locator('text=Last 6 Hours').click();
      await expect(page.locator('text=Last 24 Hours')).toBeVisible();
      await page.locator('text=Last 24 Hours').click();
      expect(page.locator('text=Last 6 Hours')).not.toBeVisible();
    });
  });
});
