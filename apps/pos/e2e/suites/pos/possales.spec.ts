import { routes, getStorageStatePath } from '@dashboard/shared-utils/e2e/constants/paths';
import { navigateTo } from '@dashboard/shared-utils/e2e/utils/common';
import { test, expect } from '../../utils/base';
import { waitForSalesAssistedScreenToLoad } from '../../utils';
import { BASE_PATH } from '../../constants';

test.describe.parallel('POS activation status @flow=pos-sales-assisted @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).POS_SALES_AGENT,
  });
  // skipping for now: https://razorpay.slack.com/archives/C061HJGS1CY/p1719569228178269
  test.skip('should render sales dashboard view if logged in as sales agent @flow=pos-sales-assisted', async ({
    page,
  }) => {
    await navigateTo(page, routes.DASHBOARD);
    await waitForSalesAssistedScreenToLoad({ page });
    await expect(page.getByText('POS Sales Dashboard')).toBeVisible();
  });
});
