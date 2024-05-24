import { getStorageStatePath, BASE_PATH } from 'testConstants';
import { expect, test } from 'utils/base';

import { navigateToSubscriptionsSettings } from './utils';

const e2eTimeout = { timeout: 30000 }; // Due to E2E infrastructure instability causing failures, increasing the timeout here to prevent E2E from failing due to API response delays or related issues.

test.describe.parallel('Payment Method @flow=subscriptions @country=MY', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).CURLEC_TEST_LOGIN_STATE,
  });

  test('should toggle Card Payment Method and Verify Notification', async ({ page }) => {
    await navigateToSubscriptionsSettings(page);

    const isSettingsVisible = await page.isVisible('text="Settings"');
    if (isSettingsVisible) {
      // Assume we are toggling the Card payment method, initial state is Enabled
      const toggleButton = page.locator('text=Card').locator('..').locator('button');
      const enabledButtonText = page.getByText('Enabled');
      const isEnabledVisible = await enabledButtonText.isVisible();

      await toggleButton.click();

      // Wait for the API call to complete and the UI to update
      await page.waitForResponse(
        (response) =>
          response.url().includes('/subscriptions/settings') && response.status() === 200,
      );

      // Verify the status text changes
      const statusText = await page
        .locator('text=Card')
        .locator('..')
        .locator('strong:visible')
        .textContent();

      const notification = page.locator('.notification-success');

      expect(statusText).toBe(isEnabledVisible ? 'Disabled' : 'Enabled');

      // Verify the success/failure notification.
      await expect(notification, e2eTimeout).toContainText(
        `Payment method card ${isEnabledVisible ? 'disabled' : 'enabled'} successfully`,
      );
    }
  });

  test("should toggle Touch 'n Go Wallet Payment Method and Verify Notification", async ({
    page,
  }) => {
    await navigateToSubscriptionsSettings(page);

    const isSettingsVisible = await page.isVisible('text="Settings"');
    if (isSettingsVisible) {
      // Locate the "Touch 'n Go Wallet" toggle button.
      const toggleButton = page.locator("text=Touch 'n Go Wallet").locator('..').locator('button');
      const enabledButtonText = page.getByText('Enabled');
      const isEnabledVisible = await enabledButtonText.isVisible();

      await toggleButton.click();

      // Wait for the API call to complete and the UI to update
      await page.waitForResponse(
        (response) =>
          response.url().includes('/subscriptions/settings') && response.status() === 200,
      );

      // Verify the status text changes.
      const statusText = await page
        .locator("text=Touch 'n Go Wallet")
        .locator('..')
        .locator('strong:visible')
        .textContent();

      const notification = page.locator('.notification-success');

      expect(statusText, e2eTimeout).toBe(isEnabledVisible ? 'Disabled' : 'Enabled');
      // Verify the success/failure notification.
      await expect(notification, e2eTimeout).toContainText(
        `Payment method Touch 'n Go Wallet ${
          isEnabledVisible ? 'disabled' : 'enabled'
        } successfully`,
      );
    }
  });
});
