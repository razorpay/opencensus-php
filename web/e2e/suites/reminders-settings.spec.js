const { test, expect } = require('@playwright/test');
const { routes } = require('../utils/constants');
const { StorageStatePath } = require('../utils/constants');

const CONSTANTS = {
  SWITCH_KNOB: 'button.checkbox-knob.checkbox-knob--prime',
  DISABLED_ALERT: 'Reminders disabled for payment links',
  ENABLED_ALERT: 'Reminders enabled for payment links',
  UPDATE_ALERT: 'Reminders are updated successfully',
};

// switch knob is expecting some value for event.pageX and event.pageY, therefore stimulating mouse movement
const stimulateMouseClick = async ({ page }) => {
  await page.waitForTimeout(5000);
  const button = await page.locator(CONSTANTS.SWITCH_KNOB);
  const { x, y } = await button.boundingBox();
  await page.mouse.click(x + 10, y + 10, { button: 'left', clickCount: 1 });
};

const expectSuccessNotification = async ({ page, notificationText }) => {
  await expect(
    page.locator('[data-testid="Notification--success"]', {
      hasText: notificationText,
    }),
  ).toBeVisible();
};

test.describe('My account and settings @flow=account-settings', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_LIVE_LOGIN_STATE,
  });

  test('should render Reminders tab', async ({ page }) => {
    // some api's are slow therefore need to increase timeout duration
    test.setTimeout(100 * 1000);
    await page.goto(routes.DASHBOARD);
    await page.getByRole('link', { name: 'Account & Settings' }).click();
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);

    await page.locator('button[role="button"]:has-text("Reminders")').click();
    await expect(page).toHaveURL(routes.REMINDERS);

    const switchKnob = await page.locator(CONSTANTS.SWITCH_KNOB);
    const switchStatus = await page.locator('span.status-text');
    expect(switchKnob).toBeVisible();
    expect(switchStatus).toBeVisible();

    const switchStatusValue = await switchStatus.textContent();
    if (switchStatusValue == 'On') {
      await stimulateMouseClick({ page });
      await page.getByRole('button', { name: 'Yes, disable' }).click();
      await expectSuccessNotification({ page, notificationText: CONSTANTS.DISABLED_ALERT });
      await page.waitForSelector('span.status-text:has-text("Off")');
    }
    await stimulateMouseClick({ page });
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
