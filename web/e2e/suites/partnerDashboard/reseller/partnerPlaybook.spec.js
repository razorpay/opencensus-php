import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';

const { test, expect } = require('utils/base');

test.describe.parallel(
  'Test Partner Playbook experience @flow=partner-playbook @project=partner-dashboard',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).RESELLER_PARTNER_TEST_LOGIN_STATE,
    });

    test('should load the Partner Playbook page and search @priority=critical', async ({
      page,
    }) => {
      await page.goto(routes.PARTNER_PLAYBOOK);
      const searchBox = await page.locator("input[name='query']");

      const getStartedHeading = page.getByRole('heading', { name: 'Get Started' });
      await expect(getStartedHeading).toBeVisible();
      await searchBox.type('kyc');
      await page.locator('button[role="button"]:has-text("Search")').click();

      await page.waitForSelector('text=Found 4 results');
    });
  },
);
