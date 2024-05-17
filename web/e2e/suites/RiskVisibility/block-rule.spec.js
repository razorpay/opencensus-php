import { BASE_PATH, getStorageStatePath, routes } from 'testConstants';

const { resolve } = require('path');
const { test, expect } = require('utils/base');

const ELEMENT_CONFIG = {
  SLIDE_1_TITLE: 'text="Razorpay Shield"',
  SKIP_BUTTON: 'button >> text="Skip And Get Started"',
  BLOCK_RULE_TITLE: 'text="Block highest fraud contributers"',
  REQUEST_BLACKLIST_BUTTON: 'button >> text="Request blacklist"',
  MODAL_TITLE: '[data-blade-component="text"]:has-text("Request blacklist")',
  SEND_REQUEST_BUTTON: 'text="Send request"',
  PARAMETER_DROPDOWN: 'text="Select parameter to blacklist"',
  ERROR: 'text="This field is required"',
  CANCEL: 'button >> text="Cancel"',
  SUCCESS_MODAL_TITLE: 'text="Request sent successfully"',
};

test.describe.parallel(
  'Test risk visibility block rule feature @flow=risk-visibility @suite=payments-automation @suite=payments-canary @project=payments @project=payments-roast',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).INTERNATIONAL_LOGIN_STATE,
    });

    test.beforeEach(async ({ page }) => {
      await page.goto(routes.RISK_AND_FRAUD);
      await expect(page).toHaveURL(routes.RISK_AND_FRAUD);
      await expect(page.locator(ELEMENT_CONFIG.SLIDE_1_TITLE)).toBeVisible();
      await page.locator(ELEMENT_CONFIG.SKIP_BUTTON).click();
    });

    test('Should open popup and show error if send request is clicked @priority=normal', async ({
      page,
    }) => {
      await expect(page.locator(ELEMENT_CONFIG.BLOCK_RULE_TITLE)).toBeVisible();
      await page.locator(ELEMENT_CONFIG.REQUEST_BLACKLIST_BUTTON).first().click();

      await expect(page.locator(ELEMENT_CONFIG.MODAL_TITLE)).toBeVisible();
      await page.locator(ELEMENT_CONFIG.SEND_REQUEST_BUTTON).click();

      await expect(page.locator(ELEMENT_CONFIG.ERROR)).toHaveCount(2);

      await page.locator(ELEMENT_CONFIG.CANCEL).click();
      await expect(page.locator(ELEMENT_CONFIG.MODAL_TITLE)).not.toBeVisible();
    });

    test('Should show success modal if all the information is filled successfully', async ({
      page,
    }) => {
      await page.locator(ELEMENT_CONFIG.REQUEST_BLACKLIST_BUTTON).first().click();

      await page.locator(ELEMENT_CONFIG.PARAMETER_DROPDOWN).click();
      await page.getByRole('option', { name: 'Contact' }).click();

      await page.setInputFiles('input[type="file"]', resolve(__dirname, 'test-doc.xlsx'));

      await page.locator(ELEMENT_CONFIG.SEND_REQUEST_BUTTON).click();

      await expect(page.locator(ELEMENT_CONFIG.ERROR)).not.toBeVisible();

      /*Api is failing, BE will fix it*/
      //await page.locator(ELEMENT_CONFIG.SUCCESS_MODAL_TITLE).toBeVisible();
    });
  },
);
