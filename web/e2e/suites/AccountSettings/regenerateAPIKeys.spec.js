import {
  test,
  expect,
  getStorageStatePath,
  waitAndProceedVerificationPopup,
  expectSuccessNotification,
} from '@libs/shared-qsuite/playwright';

const ELEMENT_CONSTANTS = {
  API_KEYS_SETTINGS_URL: '/app/website-app-settings/api-keys',
  GENERATE_KEY_CTA: 'Generate new key',
  ALERT_OK_CTA: "xpath=//div[@class='Modal__actions']//button[contains(text(),'OK')]",
  VERIFICATION_MODAL: "xpath=//div[@class='otp-input']//div",
  SUBMIT_VERIFICATION_MODAL:
    "xpath=//div[@class='Modal__actions']//button[contains(text(),'Confirm')]",
  PROMPT_COMFIRM_CTA: "xpath=//button[@class='btn btn-xs btn-primary']//span",
  GENERATE_KEY_SUCCESS_NOTIFICATION: 'New Key Generated Successfully',
  API_KEY_REGEXP: /^rzp_live_[A-Za-z0-9]+$/,
};

test.describe('Test Regenerate API Keys @flow=account-settings @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });
  test.skip('should regenerate API Keys @priority=critical', async ({ page }) => {
    // go to the api keys and settings tab
    await page.goto(ELEMENT_CONSTANTS.API_KEYS_SETTINGS_URL);

    // generate api key button to be visible and click it
    const generateApiKeyCTA = await page.getByText(ELEMENT_CONSTANTS.GENERATE_KEY_CTA, {
      exact: false,
    });
    await generateApiKeyCTA.click();

    await waitAndProceedVerificationPopup({ page });

    // wait for generate deactivate old key button to be visible and click it
    const deactivateOldCTA = await page.waitForSelector('text=Deactivate old key immediately');
    await deactivateOldCTA.click();

    // wait for generate confirm button to be visible and click it
    const confirmCTA = await page.waitForSelector('button:text("Confirm and deactivate")');
    await confirmCTA.click();

    await expectSuccessNotification({
      page,
      notificationText: ELEMENT_CONSTANTS.GENERATE_KEY_SUCCESS_NOTIFICATION,
    });

    // expect newly created key to be visible
    await expect(page.getByText(ELEMENT_CONSTANTS.API_KEY_REGEXP)).toBeVisible();
  });
});
