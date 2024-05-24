import { BASE_PATH, getStorageStatePath, routes } from 'testConstants';

const { test, expect } = require('utils/base');

const ELEMENT_CONFIG = {
  SLIDE_1_TITLE: 'text="Razorpay Shield"',
  READ_MORE_BUTTON: 'button >> text="Read More"',
  SLIDE_2_TITLE: 'text="Understanding Frauds and Disputes"',
  BACK_BUTTON: 'button >> text="Back"',
  SKIP_BUTTON: 'button >> text="Skip And Get Started"',
  RISK_ANALYTICS_TEXT: 'a[aria-label="Risk Analytics"]',
  QUICK_GUIDE_TITLE: 'text="Understanding Frauds and Disputes"',
  QUICK_GUIDE_CLOSE: 'button[aria-label="quick-guide-close"]',
};

const TEST_DESCRIPTION =
  'Risk Visibility Dashboard @flow=risk-visibility @suite=payments-automation @suite=payments-canary @project=payments @project=payments-roast';

test.describe.parallel(TEST_DESCRIPTION, () => {
  test.use({ storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT });

  // feature flag - "show_intl_risk_dashboard"
  test('show "Risk Visibility" page when feature flag is enabled @priority=normal', async ({
    page,
  }) => {
    await page.goto(routes.RISK_AND_FRAUD);
    await expect(page).toHaveURL(routes.RISK_AND_FRAUD);
  });

  test('should show Onboarding slides', async ({ page }) => {
    await page.goto(routes.RISK_AND_FRAUD);
    await expect(page.locator(ELEMENT_CONFIG.SLIDE_1_TITLE)).toBeVisible();
    //red more click should move to next slide
    await page.locator(ELEMENT_CONFIG.READ_MORE_BUTTON).click();
    await expect(page.locator(ELEMENT_CONFIG.SLIDE_2_TITLE)).toBeVisible();
    //back button should move to prev slide
    await page.locator(ELEMENT_CONFIG.BACK_BUTTON).click();
    await expect(page.locator(ELEMENT_CONFIG.SLIDE_1_TITLE)).toBeVisible();
    //skip button should close the onboarding
    await page.locator(ELEMENT_CONFIG.SKIP_BUTTON).click();
    await expect(page.locator(ELEMENT_CONFIG.RISK_ANALYTICS_TEXT)).toBeVisible();
  });

  test('should show Quick Guide component', async ({ page }) => {
    await page.goto(routes.RISK_AND_FRAUD);
    await expect(page.locator(ELEMENT_CONFIG.SLIDE_1_TITLE)).toBeVisible();
    //skip button should close the onboarding
    await page.locator(ELEMENT_CONFIG.SKIP_BUTTON).click();
    await expect(page.locator(ELEMENT_CONFIG.QUICK_GUIDE_TITLE)).toBeVisible();
    await page.locator(ELEMENT_CONFIG.QUICK_GUIDE_CLOSE).click();
    await expect(page.locator(ELEMENT_CONFIG.SLIDE_1_TITLE)).not.toBeVisible();
  });
});
