import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { tabOpenInNewTab, tabOpenInSameTab, scrollAndVerify, clickMore } from './utils';

test.describe.parallel('One nav dashboard landing page @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await clickMore(page);
  });

  test('should display the logo in the top navigation', async ({ page }) => {
    const brandLogo = await page.locator('svg[data-testid="brand-logo"]');
    await expect(brandLogo).toBeVisible();
  });

  test(`should navigate to payments dashboard`, async ({ page }) => {
    await page.locator(`button:has-text("Accept Payments")`).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: routes.DASHBOARD,
      expectedTextRegex: 'Payments Overview',
    });
  });

  test(`should navigate to banking+ dashboard`, async ({ page }) => {
    await tabOpenInNewTab({
      page,
      buttonText: 'Banking+',
      expectedUrl: 'https://x.razorpay.com/auth?utm_source=r1_dashboard&utm_content=top_nav',
      expectedTitleRegex: 'RazorpayX',
      expectedTextRegex: 'Banking made awesome',
    });
  });

  test(`should navigate to payroll dashboard`, async ({ page }) => {
    await tabOpenInNewTab({
      page,
      buttonText: 'Payroll',
      expectedUrl: 'https://payroll.razorpay.com/login',
      expectedTitleRegex: 'RazorpayX Payroll',
      expectedTextRegex: 'Smart payroll for smarter businesses',
    });
  });

  test(`should navigate to rize dashboard`, async ({ page }) => {
    await tabOpenInNewTab({
      page,
      buttonText: 'Rize',
      expectedUrl: 'https://razorpay.com/rize?utm_source=r1_dashboard&utm_content=top_nav',
      expectedTitleRegex: 'Razorpay Rize',
      expectedTextRegex: 'What is Razorpay Rize?',
    });
  });
});

test.describe.parallel('One nav partner dashboard landing page @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_2,
  });
  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await clickMore(page);
  });

  test(`should navigate to partners dashboard`, async ({ page }) => {
    await page.locator(`button:has-text("Partners")`).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: routes.PARTNER_DASHBOARD,
      expectedTextRegex: 'Partner Dashboard',
    });
  });
});

test.describe.parallel('One nav company registration landing page @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_3,
  });
  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await clickMore(page);
  });

  test(`should navigate to company registration dashboard`, async ({ page }) => {
    await page.locator(`button:has-text("Company Registration")`).click();

    await tabOpenInSameTab({
      page,
      expectedUrl: routes.COMPANY_REGISTRATION,
      expectedTextRegex: 'contact us onrize-registrations@razorpay.com',
    });
  });
});

test.describe.parallel('One nav scroll Verification @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await page.locator('.main-content--one-dashboard').waitFor({
      state: 'visible',
      timeout: 60000,
    });
  });

  test('should scroll when side nav present', async ({ page }) => {
    await scrollAndVerify(page, 'div[data-testid="carousel-widget-wrapper"]');
  });

  test('should scroll for full page view', async ({ page }) => {
    await page.getByRole('link', { name: 'Apps & Deals' }).click();
    await scrollAndVerify(page, '.partner-products-container');
  });
});

test.describe.parallel('One nav "Partner Onboarding Modal" @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await clickMore(page);
  });

  test(`should open partner onboarding modal`, async ({ page }) => {
    await page.locator(`button:has-text("Partners")`).click();

    await tabOpenInSameTab({
      page,
      expectedUrl:
        'https://dashboard.dev.razorpay.in/app/dashboard?openModal=partners_onboarding_modal',
      expectedTextRegex: 'Welcome to your Partner Dashboard',
    });
  });
});
