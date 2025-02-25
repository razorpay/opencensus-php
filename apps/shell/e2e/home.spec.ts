import { test, expect } from '@playwright/test';

test.describe.parallel('Test Home @flow=home', () => {
  test('should show heading when visiting home page @priority=normal', async ({ page }) => {
    await page.goto('/');
    const heading = page.locator('role=heading[name="Welcome to Frontend Universe!"]');
    await expect(heading).toBeVisible();
  });
});
