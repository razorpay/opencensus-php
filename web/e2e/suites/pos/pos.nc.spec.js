import {
  routes,
  test,
  expect,
  getStorageStatePath,
  navigateTo,
} from '@libs/shared-qsuite/playwright';

const { waitForPosNcModalToLoad } = require('./utils');

test.describe.parallel('POS activation status @flow=pos-activation-status', () => {
  test.use({
    storageState: getStorageStatePath().POS_KYC_STATUS_NC,
  });

  test('should render need clarification modal @flow=pos-activation-status @project=pos-onboarding', async ({
    page,
  }) => {
    await navigateTo({ page }, routes.DASHBOARD);
    await waitForPosNcModalToLoad({ page });
    await expect(
      page.getByText('We need a few clarifications to complete POS KYC verification'),
    ).toBeVisible();
  });
});
