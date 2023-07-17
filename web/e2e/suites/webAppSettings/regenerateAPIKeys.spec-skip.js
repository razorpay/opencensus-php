const { test, expect } = require('@playwright/test');
const { StorageStatePath } = require('../../utils/constants');

const ELEMENT_CONSTANTS = {
  API_KEYS_SETTINGS_URL: '/app/website-app-settings/api-keys',
  GENERATE_KEY_CTA: "xpath=//button[@class='btn btn-xs btn-primary']//span",
  ALERT_OK_CTA: "xpath=//div[@class='Modal__actions']//button[contains(text(),'OK')]",
  PROMPT_COMFIRM_CTA: "xpath=//button[@class='btn btn-xs btn-primary']//span",
};

test.describe('Test Regenerate API Keys @flow=settings @project=payments', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });
  test.skip('should regenerate API Keys @priority=critical', async ({ page }) => {
    // go to the api keys and settings tab
    await page.goto(ELEMENT_CONSTANTS.API_KEYS_SETTINGS_URL);

    // wait for generate api key button to be visible and click it
    const generateApiKeyCTA = await page.waitForSelector(ELEMENT_CONSTANTS.GENERATE_KEY_CTA, {
      timeout: 5000,
    });
    await generateApiKeyCTA.click();

    // wait for generate deactivate old key button to be visible and click it
    const deactivateOldCTA = await page.waitForSelector('text=Deactivate old key immediately', {
      timeout: 5000,
    });
    await deactivateOldCTA.click();

    // wait for generate confirm button to be visible and click it
    const confirmCTA = await page.waitForSelector('button:text("Confirm and deactivate")', {
      timeout: 5000,
    });
    await confirmCTA.click();

    // wait for new key to showup
    await page.waitForSelector('text=New Key', { timeout: 5000 });
    // get the new api key
    const newAPIKey = await page.locator('input[name="keyId"]').inputValue();
    await page.locator('button:text("OK")').click();

    // click on confirm alert
    const alertOkCTA = await page.waitForSelector(ELEMENT_CONSTANTS.ALERT_OK_CTA, {
      timeout: 5000,
    });
    await alertOkCTA.click();

    // expect newly created key to be visible
    await expect(page.locator(`text=${newAPIKey}`)).toBeVisible();
  });
});
