import { routes, test, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { tabOpenInSameTab } from './utils';

async function expandPaymentProductSideNav(page) {
  await page
    .locator('nav[data-blade-component="sidenav"] button')
    .filter({ hasText: /^\+\d+ More$/ })
    .click();
}

test.describe.parallel('One nav payments dashboard side nav tabs @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
  });

  test(`should navigate to Transactions`, async ({ page }) => {
    await page.getByRole('link', { name: 'Transactions' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/payments',
      expectedTextRegex: 'Collected Amount',
    });
  });

  test(`should navigate to Settlement`, async ({ page }) => {
    await page.getByRole('link', { name: 'Settlements' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/settlements',
      expectedTextRegex: 'View settlement',
    });
  });

  test(`should navigate to reconciliation`, async ({ page }) => {
    await page.getByRole('link', { name: 'Reconciliation' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: 'app/reconciliations/dashboard/processes',
      expectedTextRegex: 'Reconciliation',
    });
  });

  test(`should navigate to Reports`, async ({ page }) => {
    await page.getByRole('link', { name: 'Reports' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/reports',
      expectedTextRegex: 'Report Type',
    });
  });

  // Banking Products
  test(`should navigate to X Banking`, async ({ page }) => {
    await page.getByRole('link', { name: 'X Banking' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/razorpayx',
      expectedTextRegex: 'Open Current Account',
    });
  });

  // Customer Products
  test(`should navigate to Customers`, async ({ page }) => {
    await page.getByRole('link', { name: 'Customers' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/customers',
      expectedTextRegex: 'New Customer',
    });
  });

  test(`should navigate to Offers`, async ({ page }) => {
    await page.getByRole('link', { name: 'Offers' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/offers',
      expectedTextRegex: 'Create New Offer',
    });
  });

  test(`should navigate to API Keys and Plugins`, async ({ page }) => {
    await page.getByRole('link', { name: 'API Keys and Plugins' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/api-keys',
      expectedTextRegex: 'integrate payments with your website',
    });
  });

  test(`should navigate to Apps and deals`, async ({ page }) => {
    await page.getByRole('link', { name: 'Apps & Deals' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/app-store',
      expectedTextRegex: 'Apps & Deals',
    });
  });
});

test.describe
  .parallel('One nav payments dashboard - "PAYMENT PRODUCTS" side nav tabs @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await expandPaymentProductSideNav(page);
  });

  test(`should navigate to Payment links`, async ({ page }) => {
    await page.getByRole('link', { name: 'Payment Links' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/paymentlinks',
      expectedTextRegex: 'Create Payment Link',
    });
  });

  test(`should navigate to Payment pages`, async ({ page }) => {
    await page.getByRole('link', { name: 'Payment Pages' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/paymentpages',
      expectedTextRegex: 'Create Payment Page',
    });
  });

  test(`should navigate to Razorpay.me`, async ({ page }) => {
    await page.getByRole('link', { name: 'Razorpay.me Link' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/payment-handle',
      expectedTextRegex: 'Introducing Razorpay.me',
    });
  });

  test(`should navigate to Invoices`, async ({ page }) => {
    await page.getByRole('link', { name: 'Invoices' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/invoices',
      expectedTextRegex: 'Create Invoice',
    });
  });

  test(`should navigate to Payment Button`, async ({ page }) => {
    await page.getByRole('link', { name: 'Payment Button' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/paymentbuttons',
      expectedTextRegex: 'Create Payment Button',
    });
  });

  test(`should navigate to QR Codes`, async ({ page }) => {
    await page.getByRole('link', { name: 'QR Codes' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/qr_codes',
      expectedTextRegex: 'Create QR Codes',
    });
  });

  test(`should navigate to Subscriptions`, async ({ page }) => {
    await page.getByRole('link', { name: 'Subscriptions' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/subscriptions',
      expectedTextRegex: 'Create New Subscription',
    });
  });

  test(`should navigate to Smart Collect`, async ({ page }) => {
    await page.getByRole('link', { name: 'Smart Collect' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/smartcollect/virtualaccounts',
      expectedTextRegex: 'Create Customer Identifier',
    });
  });

  test(`should navigate to Route`, async ({ page }) => {
    await page.getByRole('link', { name: 'Route' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/route/payments',
      expectedTextRegex: 'route payments',
    });
  });

  test(`should navigate to Checkout Rewards`, async ({ page }) => {
    await page.getByRole('link', { name: 'Checkout Rewards' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/checkout-rewards',
      expectedTextRegex: 'Give your customers exciting rewards with every purchase!',
    });
  });

  test(`should navigate to Konnect`, async ({ page }) => {
    await page.getByRole('link', { name: 'Konnect' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/magic-konnect',
      expectedTextRegex: 'An end to end WhatsApp engagement suite for your business',
    });
  });

  test(`should navigate to Customer Trust`, async ({ page }) => {
    await page.getByRole('link', { name: 'Customer Trust' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/customer-trust',
      expectedTextRegex: 'Razorpay Buyer Protection',
    });
  });

  test(`should navigate to Optimizer`, async ({ page }) => {
    await page.getByRole('link', { name: 'Optimizer' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/optimizer/onboarding',
      expectedTextRegex: 'Automatically route payments across multiple gateways.',
    });
  });

  test(`should navigate to Payment Metrics`, async ({ page }) => {
    await page.getByRole('link', { name: 'Payment Metrics' }).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: '/app/payment-metrics',
      expectedTextRegex: 'Overall Conversion rate',
    });
  });
});
