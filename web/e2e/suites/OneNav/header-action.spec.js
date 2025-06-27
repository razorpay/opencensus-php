import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';
import { waitForOneDashboard } from './utils';

test.describe.parallel('One nav header action @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await page.locator('.dashboard-home').waitFor({
      state: 'visible',
      timeout: 60000,
    });
  });

  test('should navigate to account settings when clicking on avatar', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();
    await page.locator('button[role="menuitem"] div[data-blade-component="avatar"]').click();
    await expect(page).toHaveURL('/app/account-settings');
  });

  test('should copy MID value and verify it matches', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();

    const midText = await page.locator('div[role="menu"] p:has-text("MID:")').textContent();
    const midValue = midText.replace('MID: ', '').trim();

    await page.locator('div[role="menu"] button[aria-label="Copy"]').click();

    const clipboardText = await page.evaluate(() => navigator.clipboard.readText());
    expect(clipboardText).toBe(midValue);
  });

  test('should navigate to support page when clicking "View Support Ticket"', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();
    await page.locator('button:has-text("View Support Tickets")').click();
    await expect(page).toHaveURL('/app/business-settings/ticket-support/tickets/merchant');
  });

  test('should open "announcements" drawer on click "View Announcements"', async ({ page }) => {
    await page.locator('main[class="whats-new-old"] button').click();
    const bodyLocator = page.locator('body');
    await expect(bodyLocator).toHaveText(new RegExp('Announcements', 'i'));
  });

  test('should open "Ecosystem Health" drawer on click "View Ecosystem Health"', async ({
    page,
  }) => {
    await page.locator('button[data-testid="ecosystem-health-check-icon"]').click();
    const bodyLocator = page.locator('body');
    await expect(bodyLocator).toHaveText(new RegExp('Ecosystem Health', 'i'));
  });

  test('individual ecosystem downtime card should open', async ({ page }) => {
    await page.locator('button[data-testid="ecosystem-health-check-icon"]').click();
    await page.locator('li[role="listitem"]').first().click();
    await page.getByLabel('ecosystem-downtime-details', { exact: true }).click();
    await page.getByTestId('ds-button').click();
  });
});

//logout is flaky on devstack, which makes asserting on the actual logout functionality test case flaky as well
test.describe.parallel('One nav header action - Logout @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_4,
  });

  test('should be able to logout', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();
    await page.locator('button:has-text("Log out")').click();
    await expect(page.locator('button:has-text("Log out")')).not.toBeVisible();
  });
});

test.describe.parallel('One nav header action when oneDashboard is enabled @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_2,
  });

  test('should show search in payments', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await waitForOneDashboard(page);
    await expect(
      page.getByPlaceholder('Search payment products, settings, and more'),
    ).toBeVisible();
  });
  test('should not show search in partners', async ({ page }) => {
    await page.goto(routes.PARTNER_DASHBOARD);
    await waitForOneDashboard(page);
    await expect(
      page.getByPlaceholder('Search payment products, settings, and more'),
    ).not.toBeVisible();
  });
});

test.describe.parallel('One nav header action - Mobile @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
    viewport: {
      width: 480,
      height: 568,
    },
    deviceScaleFactor: 2,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await page.locator('.dashboard-home').waitFor({
      state: 'visible',
      timeout: 60000,
    });
  });

  test('should navigate to account settings when clicking on avatar', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();
    const menuItem = page.locator('button[role="menuitem"] div[data-blade-component="avatar"]');
    await expect(menuItem).toBeVisible({ timeout: 5000 });

    await menuItem.click();
    await expect(page).toHaveURL('/app/account-settings');
  });

  test('should copy MID value and verify it matches', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();

    const midContainer = page.locator(
      'div[data-blade-component="bottom-sheet"] p:has-text("MID:")',
    );
    await expect(midContainer).toBeVisible({ timeout: 6000 });

    const midText = await midContainer.textContent();
    const midValue = midText.replace('MID: ', '').trim();

    await page
      .locator('div[data-blade-component="bottom-sheet"] button[aria-label="Copy"]')
      .click();

    const clipboardText = await page.evaluate(() => navigator.clipboard.readText());
    expect(clipboardText).toBe(midValue);
  });

  test('should navigate to support page when clicking "View Support Ticket"', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();
    const menuItem = page.locator('button:has-text("View Support Tickets")');
    await expect(menuItem).toBeVisible({ timeout: 6000 });
    await menuItem.click();
    await expect(page).toHaveURL('/app/business-settings/ticket-support/tickets/merchant');
  });

  test('should open "announcements" drawer on click "View Announcements"', async ({ page }) => {
    await page.locator('main[class="whats-new-old"] button').click();
    const bodyLocator = page.locator('body');
    await expect(bodyLocator).toHaveText(new RegExp('Announcements', 'i'));
  });

  test('should open "Ecosystem Health" drawer on click "View Ecosystem Health"', async ({
    page,
  }) => {
    await page.locator('button[data-testid="ecosystem-health-check-icon"]').click();
    const bodyLocator = page.locator('body');
    await expect(bodyLocator).toHaveText(new RegExp('Ecosystem Health', 'i'));
  });
});

test.describe
  .parallel('One nav header action when oneDashboard is enabled - Mobile @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_2,
    viewport: {
      width: 480,
      height: 568,
    },
    deviceScaleFactor: 2,
  });

  test('should show search in payments', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await waitForOneDashboard(page);
    await expect(
      page.getByPlaceholder('Search payment products, settings, and more'),
    ).toBeVisible();
  });

  test('should not show search in partners', async ({ page }) => {
    await page.goto(routes.PARTNER_DASHBOARD);
    await waitForOneDashboard(page);
    await expect(
      page.getByPlaceholder('Search payment products, settings, and more'),
    ).not.toBeVisible();
  });
});
