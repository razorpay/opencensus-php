import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

test.describe.parallel('One nav header action @project=oneNav', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_ONE_NAV_MERCHANT_1,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await page.locator('.dashboard-home').waitFor({ state: 'attached' });
    await page.locator('.dashboard-home').waitFor({ state: 'visible' });
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

  // test('should show login page after logout', async ({ page }) => {
  //   await page.locator('button:has-text("Log out")').click();
  //   await page.waitForURL('**');
  //   await expect(page.locator('text=Login to Dashboard')).toBeVisible();
  // });
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
    await page.locator('.dashboard-home').waitFor({ state: 'attached' });
    await page.locator('.dashboard-home').waitFor({ state: 'visible' });
  });

  test('should navigate to account settings when clicking on avatar', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();
    const menuItem = page.locator('button[role="menuitem"] div[data-blade-component="avatar"]');
    await expect(menuItem).toBeVisible({ timeout: 1000 });

    await menuItem.click();
    await expect(page).toHaveURL('/app/account-settings');
  });

  test('should copy MID value and verify it matches', async ({ page }) => {
    await page.locator('div[data-blade-component="avatar"] button').click();

    const midContainer = page.locator(
      'div[data-blade-component="bottom-sheet"] p:has-text("MID:")',
    );
    await expect(midContainer).toBeVisible({ timeout: 2000 });

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
    await expect(menuItem).toBeVisible({ timeout: 1000 });
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
