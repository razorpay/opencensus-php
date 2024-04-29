import { expect, test } from '@playwright/test';
import { BASE_PATH, getStorageStatePath, routes } from 'testConstants';
import { generateRandomWebsiteUrl, generateRandomEmail } from 'utils';

test.describe.parallel(
  'Test customer support details @flow=customer-support @project=payments',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).EMAIL_LIVE_LOGIN_STATE,
    });

    test('should show customer support details @priority=normal', async ({ page }) => {
      await page.goto(routes.DASHBOARD);
      await page.getByRole('link', { name: 'Account & Settings' }).click();
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);

      // go to customer support tab
      await page.locator('button[role="button"]:has-text("Customer support details")').click();
      await expect(page).toHaveURL(routes.CUSTOMER_SUPPORT_DETAILS);

      // verify customer support details
      const contactNumberField = await page.locator('p:has-text("Phone Number")');
      await expect(contactNumberField).toBeVisible();
      const emailField = await page.locator('p:has-text("Email")');
      await expect(emailField).toBeVisible();
      const websiteUrlField = await page.locator('p:has-text("Website/ Contact Us Link")');
      await expect(websiteUrlField).toBeVisible();
    });

    test.skip('should edit support details @priority=normal', async ({ page }) => {
      await page.goto(routes.CUSTOMER_SUPPORT_DETAILS);

      // edit website url of customer support details

      // opening support details popup to edit website url
      await page.locator('[data-testid="website"]').click();
      await page.waitForSelector('input[name="url"]');
      await page.click('input[name="url"]');

      // generating random website url
      const newUrl = generateRandomWebsiteUrl();

      // filling new url in edit form
      await page.fill('input[name="url"]', newUrl);

      // submitting the edit form
      await page.isVisible('button:text("Submit")');
      await page.locator('button:text("Submit")').click();

      // verifying the updated website url
      await expect(page.locator(`text="${newUrl}"`)).toBeVisible();

      // edit email of customer support details

      // opening support details popup to edit website url
      await page.locator('[data-testid="email"]').click();
      await page.waitForSelector('input[name="email"]');
      await page.click('input[name="email"]');

      // generating random support email
      const newEmail = generateRandomEmail();

      // filling new email in edit form
      await page.fill('input[name="email"]', newEmail);

      // submitting the edit form
      await page.isVisible('button:text("Submit")');
      await page.locator('button:text("Submit")').click();

      // verifying the updated email
      await expect(page.locator(`text="${newEmail}"`)).toBeVisible();
    });
  },
);
