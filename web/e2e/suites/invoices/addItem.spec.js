import { expect, test } from 'utils/base';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { clickSkipAndStartBtn } from 'utils';

import { createNewItem } from './utils';
test.describe
  .parallel('Test Invoices @flow=invoices @project=no-code @project=no-code-stable', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.ITEMS);
    await clickSkipAndStartBtn({ page });
  });

  test.skip('should create new item', async ({ page }) => {
    const itemName = await createNewItem({ page });
    await expect(await page.getByText(itemName)).toBeVisible();
  });
});
