const { test, expect } = require('@playwright/test');
const { resolve } = require('path');
const { generateRandomText } = require('../../utils');
const { StorageStatePath } = require('../../utils/constants');

const CONSTANTS = {
  IE_TAB_URL: '/app/payment-methods/international-payments',
  NC_BANNER: 'text="We need a few more details for your international cards payment request"',
  REQUEST_CTA: 'button[data-blade-component="button"] >> text="Request to activate"',
  SUBMIT_CTA: 'xpath=//div[6]/div/div/div/div[2]/div/div[6]/button[2]',
  NEXT_CTA: 'button >> text="Next"',
  SELECT_RADIO: 'div[class="Input-inlineLabel"] >> text="No"',
  TERMS_AND_CONDITION_CHECKBOX: 'div[class="Input-elWrapper"] > label',
};

test.describe.parallel('Test International enablement @flow=ie @project=payments', () => {
  test.use({
    storageState: StorageStatePath.TRANSACTIONS_LOGIN_STATE,
  });
  // TODO: enable these tests after multiple auth setup is done
  test('should be able to request for IE @priority=normal', async ({ page }) => {
    // navigate to IE Route
    await page.goto(CONSTANTS.IE_TAB_URL);

    // assert presence of IE Request CTA
    await page.waitForSelector(CONSTANTS.REQUEST_CTA, {
      timeout: 60000,
    });
    const requestCTA = page.locator(CONSTANTS.REQUEST_CTA);
    // wait for new customers button to be visible and click it
    await expect(requestCTA).toBeVisible();

    // click on request CTA to open IE modal
    await requestCTA.click();
    await page.isVisible('text=International activation form', {
      timeout: 5000,
    });

    // check for Business Details section
    await page.waitForSelector('text="BUSINESS DETAILS"', {
      timeout: 5000,
    });

    await page
      .locator('label')
      .filter({ hasText: 'Payment Gateway' })
      .locator('div')
      .first()
      .dblclick();

    await page.locator('select[name="goods_type"]').selectOption('both');

    const addNoteTextArea = page.locator("textarea[name='business_use_case']");
    await addNoteTextArea.fill('');
    await addNoteTextArea.fill(generateRandomText(100));

    // navigate to next section
    const nextCTA = page.locator(CONSTANTS.NEXT_CTA);
    await nextCTA.click();

    // check for Supporting details and best practices section
    await page.waitForSelector('text="SUPPORTING DETAILS"', {
      timeout: 5000,
    });
    const riskCheckList = page.locator(CONSTANTS.SELECT_RADIO);
    await riskCheckList.click();

    const termsAndConditions = page.locator(CONSTANTS.TERMS_AND_CONDITION_CHECKBOX);
    await termsAndConditions.click();

    // assert for form submit CTA
    await expect(page.locator('button >> text="Submit & Verify"')).toBeVisible();
  });

  test.skip('should be able to submit NC for IE @priority=normal', async ({ page }) => {
    // navigate to IE Route
    await page.goto(CONSTANTS.IE_TAB_URL);

    // assert presence of IE NC Banner
    await page.waitForSelector(CONSTANTS.NC_BANNER, {
      timeout: 20000,
    });
    await expect(page.locator(CONSTANTS.NC_BANNER)).toBeVisible();

    // click on IE NC CTA
    await page.isVisible('button:text("Submit details now")', {
      timeout: 5000,
    });
    await page
      .locator('button', {
        hasText: 'Submit details now',
      })
      .click();

    // check presence of NC Modal
    await page.isVisible('text=Update details as per the instructions below', {
      timeout: 5000,
    });

    const submitCTA = page.locator(CONSTANTS.SUBMIT_CTA);

    // expect submit button to be disabled initially
    await expect(submitCTA).toBeDisabled({
      timeout: 5000,
    });

    const addNoteTextArea = page.locator("textarea[name='note']");
    await addNoteTextArea.type(generateRandomText(100));

    await page.setInputFiles('input[type="file"]', resolve(__dirname, 'test-doc.jpeg'));

    submitCTA.click({
      trial: true,
    });

    // assert submit button to be enabled
    await expect(submitCTA).toBeEnabled({
      timeout: 5000,
    });
  });
});
