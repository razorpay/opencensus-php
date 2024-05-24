import { expect, test } from 'utils/base';

import { navigateToOptimizer } from './utils';

const { BASE_PATH, getStorageStatePath } = require('testConstants');

test.describe.parallel('Optimizer (Live Mode) @flow=optimizer @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.describe.parallel('Optimizer Landing screen Provider section', () => {
    test('should show providers section', async ({ page }) => {
      await navigateToOptimizer(page);
      await expect(page.getByText('Payment Provider')).toBeVisible();
      await expect(page.getByRole('link', { name: 'Documentation' })).toBeVisible();
      await expect(page.getByRole('button', { name: 'Add Provider' })).toBeVisible();
    });
  });
});
