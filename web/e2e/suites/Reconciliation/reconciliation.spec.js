const { test, expect } = require('@playwright/test');
const { routes, getStorageStatePath, BASE_PATH } = require('testConstants');

test.describe.parallel('@flow=recon-saas @suite=recon-saas @project=recon-saas', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).EMAIL_TEST_LOGIN_STATE,
  });

  test('should be able to load recon dashboard', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await page.goto(routes.RECON_DASHBOARD);
    await expect(page.getByText('Processes')).toBeVisible();
  });

  test('should be able to load recon processes', async ({ page }) => {
    await page.goto(routes.RECON_DASHBOARD);
    const processContainer = page.locator('[data-testid="recon-process-listing"]');
    const table = processContainer.locator('.table-responsive').first();
    await expect(table).toBeVisible();
  });

  test('should be able to load recon runs', async ({ page }) => {
    await page.goto(routes.RECON_DASHBOARD);
    await page.getByText('Runs').click();
    const runsContainer = page.locator('[data-testid="recon-runs-listing"]');
    const table = runsContainer.locator('.table-responsive').first();
    await expect(table).toBeVisible();
  });
  test('should be able to load process overview stats', async ({ page }) => {
    await page.goto(routes.RECON_DASHBOARD);
    const processContainer = page.locator('[data-testid="recon-process-listing"]');
    const table = processContainer.locator('.table-responsive').first();
    await table.getByRole('link', { name: 'Details' }).first().click();
    // If reconciled is shown which means the process overview stats are loaded
    const stats = page.locator('[data-testid="recon-overview-page"]');
    await expect(stats).toBeVisible();
  });
  test('should be able to load process overview charts', async ({ page }) => {
    await page.goto(routes.RECON_DASHBOARD);
    const processContainer = page.locator('[data-testid="recon-process-listing"]');
    const table = processContainer.locator('.table-responsive').first();
    await table.getByRole('link', { name: 'Details' }).first().click();
    // If title of chart is shown, it means chart is loaded
    const charts = page.locator('[data-testid="recon-overview-charts"]');
    await expect(charts).toBeVisible();
  });
  test('should be able to render runs under process', async ({ page }) => {
    await page.goto(routes.RECON_DASHBOARD);
    const processContainer = page.locator('[data-testid="recon-process-listing"]');
    const table = processContainer.locator('.table-responsive').first();
    await table.getByRole('link', { name: 'Details' }).first().click();
    await page.getByRole('tab', { name: 'Runs' }).click();
    const processRuns = page.locator('[data-testid="recon-process-runs"]');
    await expect(processRuns).toBeVisible();
  });
  test('should be able to render transactions runs under process', async ({ page }) => {
    await page.goto(routes.RECON_DASHBOARD);
    const processContainer = page.locator('[data-testid="recon-process-listing"]');
    const table = processContainer.locator('.table-responsive').first();
    await table.getByRole('link', { name: 'Details' }).first().click();
    await page.getByRole('tab', { name: 'Transactions' }).click();
    const processTransactions = page.locator('[data-testid="recon-process-transactions"]');
    await expect(processTransactions).toBeVisible();
  });
});
