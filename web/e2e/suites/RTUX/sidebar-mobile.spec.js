import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

test.use({
  viewport: {
    width: 320,
    height: 568,
  },
  deviceScaleFactor: 2,
});

test.describe.parallel('RTUX - sidebar @flow=rtux @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().SETTLEMENTS_LOGIN_STATE,
  });

  test('should show hamburger icon @priority=normal', async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    const hamburgerIcon = await page.getByTestId('header-menu-icon').locator('svg');
    await expect(hamburgerIcon).toBeVisible();
    await hamburgerIcon.click();
    const rzpLogo = await page.getByLabel('brand-logo home page link');
    await expect(rzpLogo).toBeVisible();
    await expect(
      page
        .getByRole('link', { name: 'Home', exact: true })
        .or(page.getByRole('link', { name: 'Selected background Home', exact: true })),
    ).toBeVisible();
    await page.locator('data-testid=sidebar-background-overlay').click({ force: true });
    await expect(rzpLogo).not.toBeVisible({
      visible: false,
    });
  });
});
