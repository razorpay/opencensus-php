import { BASE_PATH, getStorageStatePath, routes } from 'testConstants';

const { test, expect } = require('utils/base');

const WEBHOOK_DATA = {
  EMAIL: 'https://www.youtube.com/71',
  STATUS: 'Enabled',
  EVENT: '1 event',
};

const ELEMENT_CONSTANTS = {
  WEBHOOK_TAB_NAME: 'Webhooks',
  API_KEYS_TAB_NAME: 'API Keys',
  WEBHOOK_ADD_CTA: '+ Add New Webhook',
  GENERATE_KEY_CTA: 'Generate new key',
  WEBHOOK_DETAILS_LOCATOR: '.content-wrapper table tbody tr:first-child',
  API_KEY_REGEXP: /^rzp_live_[A-Za-z0-9]+$/,
};

const verifyAndRedirectPageRoute = async ({ page, buttonName, route }) => {
  // redirect to tab from account and settings home page
  await page.getByRole('button', { name: buttonName }).click();
  // verifying tab url matches
  await expect(page).toHaveURL(route);
};

test.describe.parallel(
  'AnS Webhooks @flow=account-settings @project=payments @project=payments-roast',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).TRANSACTIONS_LOGIN_STATE,
    });

    test.beforeEach(async ({ page }) => {
      await page.goto(routes.ACCOUNT_SETTINGS);
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
    });

    test('should show add webhook button @priority=normal @suite=payments-automation @suite=payments-canary', async ({
      page,
    }) => {
      await verifyAndRedirectPageRoute({
        page,
        buttonName: ELEMENT_CONSTANTS.WEBHOOK_TAB_NAME,
        route: routes.WEBHOOKS,
      });

      // verifying add webhook action is visible
      await expect(
        page.getByRole('button', { name: ELEMENT_CONSTANTS.WEBHOOK_ADD_CTA }),
      ).toBeVisible();
    });

    test('should show webhooks in table view @priority=normal @suite=payments-automation @suite=payments-canary', async ({
      page,
    }) => {
      await verifyAndRedirectPageRoute({
        page,
        buttonName: ELEMENT_CONSTANTS.WEBHOOK_TAB_NAME,
        route: routes.WEBHOOKS,
      });

      // fetching webhook details from webhook details table
      const webhooksDetails = await page.locator(ELEMENT_CONSTANTS.WEBHOOK_DETAILS_LOCATOR);
      expect(webhooksDetails).toBeVisible();

      // verify webhook details like email, status and event
      await expect(webhooksDetails.getByText(WEBHOOK_DATA.EMAIL, { exact: true })).toBeVisible();
      await expect(webhooksDetails.getByText(WEBHOOK_DATA.STATUS)).toBeVisible();
      await expect(webhooksDetails.getByText(WEBHOOK_DATA.EVENT)).toBeVisible();
    });
  },
);

test.describe.parallel(
  'AnS API Keys @flow=account-settings @project=payments @project=payments-roast',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).TRANSACTIONS_LOGIN_STATE,
    });

    test.beforeEach(async ({ page }) => {
      await page.goto(routes.ACCOUNT_SETTINGS);
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
    });

    test('should show API keys @priority=normal @suite=payments-automation @suite=payments-canary', async ({
      page,
    }) => {
      await verifyAndRedirectPageRoute({
        page,
        buttonName: ELEMENT_CONSTANTS.API_KEYS_TAB_NAME,
        route: routes.API_KEYS,
      });
      await expect(
        await page.getByText('Let’s integrate payments with your website/app', { exact: true }),
      ).toBeVisible();

      // verifing api key exists using regex
      await expect(page.getByText(ELEMENT_CONSTANTS.API_KEY_REGEXP)).toBeVisible();

      // verifing generate new key using action
      await expect(
        await page.getByText(ELEMENT_CONSTANTS.GENERATE_KEY_CTA, { exact: false }),
      ).toBeVisible();
    });
  },
);
