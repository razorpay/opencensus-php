import { BASE_PATH, getStorageStatePath } from 'testConstants';

const { test, expect } = require('utils/base');

const ELEMENT_CONSTANTS = {
  WEBHOOK_SETTINGS_URL: '/app/website-app-settings/webhooks',
  ADD_WEBHOOK_CTA: 'button:text("+ Add New Webhook")',
  ALERT_OK_CTA: "xpath=//div[@class='Modal__actions']//button[contains(text(),'OK')]",
  PROMPT_COMFIRM_CTA: "xpath=//button[@class='btn btn-xs btn-primary']//span",
};

test.describe('Test webhook creation @flow=settings @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });
  test.skip('should create webhook @priority=critical', async ({ page }) => {
    // go to the webhook settings tab
    await page.goto(ELEMENT_CONSTANTS.WEBHOOK_SETTINGS_URL);

    // wait for generate add new webhook button to be visible and click it
    const addWebHookCTA = page.locator(ELEMENT_CONSTANTS.ADD_WEBHOOK_CTA);
    await addWebHookCTA.waitFor({ state: 'visible' });
    await expect(addWebHookCTA).toBeVisible();
    addWebHookCTA.click();

    // create random inputs for testing webhook
    const randomHash = Math.random().toString(36).slice(2, 15);
    const testWebhookUrl = `https://test.com/${randomHash}`;

    await page.waitForTimeout(3000);

    // fill webhook url in input
    const urlInput = await page.waitForSelector('input[name="url"]');
    await urlInput.click();
    await urlInput.fill(testWebhookUrl);

    // fill secret hash
    const secretInput = await page.waitForSelector('input[name="secret"]');
    await secretInput.click();
    await secretInput.fill(randomHash);

    // select some event for webhook
    const paymentAuthorizedCheckBox = await page.waitForSelector('text="payment.authorized"');
    await paymentAuthorizedCheckBox.click();

    // click on create webhook CTA
    await page.click('text="Create Webhook"');

    // expect newly created webhook to be visible and click ont it
    const newWebhook = page.locator(`text="${testWebhookUrl}"`);
    await newWebhook.waitFor({ state: 'visible' });
    await expect(newWebhook).toBeVisible();
    newWebhook.click();

    // cleanup - delete webhook
    const deleteWebhookCTA = page.locator('.delete-webhook');
    await deleteWebhookCTA.waitFor({ state: 'visible' });
    await expect(deleteWebhookCTA).toBeVisible();
    deleteWebhookCTA.click();

    await page.click('text="Yes, Delete"');
  });
});
