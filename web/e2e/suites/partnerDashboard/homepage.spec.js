import { WELCOME_TEXT_SELECTORS } from './constants';
import { waitForSelectorToBeVisible } from '../../utils/common';
import { routes, StorageStatePath } from '../../utils/constants';

const { test } = require('@playwright/test');
const { RESELLER_WELCOME_TEXT, AGGREGATOR_WELCOME_TEXT, PLATFORM_PARTNER_WELCOME_TEXT } =
  WELCOME_TEXT_SELECTORS;

const TIMEOUT = 20 * 1000;

// Reseller Partner Tests
test.describe
  .parallel('Test Reseller Partner Dashboard landing page @flow=partner-homepage @project=partner-dashboard', () => {
  test.use({
    storageState: StorageStatePath.RESELLER_PARTNER_TEST_LOGIN_STATE,
  });
  test.beforeEach(async ({ page }) => {
    await page.goto(routes.PARTNER_DASHBOARD);
  });
  test('should load the Reseller Partner Dashboard @priority=critical', async ({ page }) => {
    await waitForSelectorToBeVisible(
      { page, selector: RESELLER_WELCOME_TEXT },
      { timeout: TIMEOUT },
    );
  });
});

// Aggregator Partner Tests
test.describe
  .parallel('Test Aggregator Partner Dashboard landing page @flow=partner-homepage @project=partner-dashboard', () => {
  test.use({
    storageState: StorageStatePath.AGGREGATOR_PARTNER_TEST_LOGIN_STATE,
  });
  test.beforeEach(async ({ page }) => {
    await page.goto(routes.PARTNER_DASHBOARD);
  });
  test('should load the Aggregator Partner Dashboard @priority=critical', async ({ page }) => {
    await waitForSelectorToBeVisible(
      { page, selector: AGGREGATOR_WELCOME_TEXT },
      { timeout: TIMEOUT },
    );
  });
});

// Platform Partner Tests
test.describe
  .parallel('Test Platform Partner Dashboard landing page @flow=partner-homepage @project=partner-dashboard', () => {
  test.use({
    storageState: StorageStatePath.PLATFORM_PARTNER_TEST_LOGIN_STATE,
  });
  test.beforeEach(async ({ page }) => {
    await page.goto(routes.PARTNER_DASHBOARD);
  });
  test('should load the Platform Partner Dashboard @priority=critical', async ({ page }) => {
    await waitForSelectorToBeVisible(
      { page, selector: PLATFORM_PARTNER_WELCOME_TEXT },
      { timeout: TIMEOUT },
    );
  });
});
