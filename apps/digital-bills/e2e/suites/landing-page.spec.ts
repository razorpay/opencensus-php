import { test, expect, getStorageStatePath, navigateTo } from '@libs/shared-qsuite/playwright';
import { ROUTES } from '@apps/digital-bills/e2e/constants';

test.describe.parallel('Landing page for BillMe @flow=digital-bills @project=payments', () => {
  test.use({
    storageState: getStorageStatePath('test').ACTIVATED_RZP_MERCHANT,
  });

  test.skip("should render 'Onboarding Landing page' in test mode for a non-BillMe merchant", async ({
    page,
  }) => {
    // Navigate to BillMe (Digital Bills) landing page
    await navigateTo({ page }, ROUTES.OVERVIEW_DASHBOARD);

    // Landing page
    await expect(
      page.getByText('Say goodbye to paper bills and unlock business growth.'),
    ).toBeVisible();
    await page.getByRole('button', { name: 'Read More' }).click();

    // Join waitlist screen
    await expect(page.getByText('What makes BillMe great?')).toBeVisible();
    await page.getByRole('button', { name: 'Join The Waitlist' }).click();

    // Store selection modal
    await expect(page.getByText('Join The Waitlist!')).toBeVisible();
    await page.getByText('50+').click();
    await expect(page.getByRole('button', { name: 'Submit' })).toBeVisible();
    await page.getByRole('button', { name: 'Cancel' }).click();
  });
});
