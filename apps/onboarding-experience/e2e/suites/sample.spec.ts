import { routes, expect, test } from '@libs/shared-qsuite/playwright';

test.describe.parallel('Sample E2E For Reference @project=sample-tests', () => {
  test.skip(`Sample e2e for checking page load with required url`, async ({ page }) => {
    await page.goto(routes.SIGN_IN_PATH);
    await expect(page).toHaveURL(routes.SIGN_IN_PATH);
  });
});
