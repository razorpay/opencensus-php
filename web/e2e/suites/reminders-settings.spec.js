const { test, expect } = require('@playwright/test');
const { mouseClickToggleSwitch } = require('../utils/common');

const { StorageStatePath, routes } = require('../utils/constants');
const { COMMON_SELECTORS } = require('../utils/selectors');
const { expectSuccessNotification } = require('../utils');

const CONSTANTS = {
  DISABLED_ALERT: 'Reminders disabled for payment links',
  ENABLED_ALERT: 'Reminders enabled for payment links',
  UPDATE_ALERT: 'Reminders are updated successfully',
};

test.setTimeout(1 * 60 * 1000);
test.describe('My account and settings @flow=account-settings', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_LIVE_LOGIN_STATE,
  });

  test('should render Reminders tab', async ({ page }) => {
    // some api's are slow therefore need to increase timeout duration
    await page.goto(routes.DASHBOARD);
    await page.getByRole('link', { name: 'Account & Settings' }).click();
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);

    await page.locator('button[role="button"]:has-text("Reminders")').click();
    await expect(page).toHaveURL(routes.REMINDERS);

    const switchKnob = await page.locator(COMMON_SELECTORS.toggleSwitch);
    const switchStatus = await page.locator('span.status-text');
    expect(switchKnob).toBeVisible();
    expect(switchStatus).toBeVisible();

    const switchStatusValue = await switchStatus.textContent();
    if (switchStatusValue == 'On') {
      await mouseClickToggleSwitch({ page, container: page });
      await page.getByRole('button', { name: 'Yes, disable' }).click();
      await expectSuccessNotification({ page, notificationText: CONSTANTS.DISABLED_ALERT });
      await page.waitForSelector('span.status-text:has-text("Off")');
    }
    await mouseClickToggleSwitch({ page, container: page });
    await expectSuccessNotification({ page, notificationText: CONSTANTS.ENABLED_ALERT });

    await expect(page.getByText('For links with an expiry date')).toBeVisible();
    await expect(page.getByText('For links without an expiry date')).toBeVisible();
    const updateChangesCTA = await page.getByRole('button', {
      name: 'Save Changes',
    });
    expect(updateChangesCTA).toBeDisabled();
    const smsCheckbox = await page.$('input[name="sms"]');
    const emailCheckbox = await page.$('input[name="email"]');

    await expect(smsCheckbox).not.toBeNull();
    await expect(emailCheckbox).not.toBeNull();

    const isEmailChecked = await emailCheckbox.isChecked();
    const isSmsChecked = await smsCheckbox.isChecked();

    const smsLabel = await page.getByText('SMS', { exact: true });
    const emailLabel = await page.getByText('Email', { exact: true });

    // turn off currently active option and turn on the other one
    if (isSmsChecked) {
      await smsLabel.click();
      await emailLabel.click();
    } else if (isEmailChecked) {
      await emailLabel.click();
      await smsLabel.click();
    } else {
      await emailLabel.click();
    }
    expect(updateChangesCTA).not.toBeDisabled();
    await updateChangesCTA.click();
    await expectSuccessNotification({ page, notificationText: CONSTANTS.UPDATE_ALERT });
  });
});
