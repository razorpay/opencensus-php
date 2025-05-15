/* eslint-disable no-await-in-loop */
import { test, expect, getStorageStatePath, navigateTo } from '@libs/shared-qsuite/playwright';
import { ROUTES, IFRAME_PATH_INFO } from '@apps/digital-bills/e2e/constants';

test.describe
  .parallel('Overview Dashboard and BillMe core modules in iframe @flow=digital-bills @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test('should render Bills section and Bill info page', async ({ page }) => {
    // Navigate to BillMe (Digital Bills) Dashboard page
    await navigateTo({ page }, ROUTES.OVERVIEW_DASHBOARD);

    await expect(page.getByText('Dashboard')).toBeVisible();

    // Check all summary cards
    await expect(page.getByText('Summary')).toBeVisible();
    await expect(page.getByText('Wallet Balance')).toBeVisible();
    await expect(page.getByText('Trees Saved')).toBeVisible();
    await expect(page.getByText('Active Stores')).toBeVisible();

    // Go to Bills View
    await page.getByRole('button', { name: 'Go to Bills View', exact: true }).click();
    await expect(page).toHaveURL(ROUTES.BILLS);

    // Bills list page
    await expect(page.getByText('Overview')).toBeVisible();
    await page.getByRole('button', { name: 'View Graphical Data' }).click();
    await expect(page.locator('canvas')).toBeVisible();
    await expect(
      page.locator('[data-blade-component="spinner"] >> [aria-label="Refreshing Table"]'),
    ).not.toBeVisible();
    const billsCount = await page.locator('[data-blade-component="table-row"]').count();
    if (billsCount > 0) {
      await page
        .locator(
          '[data-blade-component="table-row"] >> [data-blade-component="base-text"]:has-text("bill_")',
        )
        .first()
        .click();
      await expect(page.getByText('Bill Details')).toBeVisible();
      await expect(page.getByText('Channel Status Report')).toBeVisible();
      await expect(page.getByText('Bill Read Receipt')).toBeVisible();

      // Bill preview iframe
      await expect(page.locator('iframe#billme-bill-preview')).toBeVisible();
    }
  });

  test.skip('should render in iframe for BillMe core modules', async ({ page }) => {
    // Navigate to BillMe (Digital Bills) Dashboard page
    await navigateTo({ page }, ROUTES.OVERVIEW_DASHBOARD);

    await expect(page.getByText('Dashboard')).toBeVisible();

    // Iframe validation
    for (const iframe of IFRAME_PATH_INFO) {
      const { linkTitle, pathname, iframePath } = iframe;
      await page.getByRole('button', { name: linkTitle, exact: true }).click();
      await expect(page).toHaveURL(pathname);
      const iframeElement = await page.locator('iframe#billme-iframe');
      const iframeUrl = await iframeElement.getAttribute('src');
      await expect(iframeUrl).not.toBe(null);
      const parsedPathname = new URL(iframeUrl).pathname;
      await expect(parsedPathname).toBe(iframePath);
      await page.getByRole('button', { name: 'Back' }).click();
      await expect(page).toHaveURL(ROUTES.OVERVIEW_DASHBOARD);
    }
  });
});
