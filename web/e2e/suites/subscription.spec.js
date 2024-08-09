import { test, expect } from '@playwright/test';
import { routes, getStorageStatePath, BASE_PATH } from 'testConstants';
import { expectSuccessNotification, generateRandomText, clickSkipAndStartBtn } from 'utils';

test.describe.serial('Test subscription @flow=subscription @project=no-code', () => {
  let planName = '';
  let subscriptionId = '';

  test.use({
    storageState: getStorageStatePath(BASE_PATH, 'test').ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.SUBSCRIPTIONS);
    await clickSkipAndStartBtn({ page });
  });

  // roast test createPlanTest
  test('should create subscription plan @priority=critical @suite=nocode-P0-automation', async ({
    page,
  }) => {
    planName = generateRandomText(10);
    await page.getByRole('link', { name: 'Plans' }).click();
    await page.getByRole('button', { name: 'New Plan' }).click();
    await page.getByPlaceholder('The name known to your customers').fill(planName);
    await page.locator('input[name="interval"]').fill('6');
    await page.getByPlaceholder('0.00').fill('100');
    await page.getByRole('button', { name: 'Create Plan' }).click();
    await expectSuccessNotification({
      page,
      notificationText: 'Plan saved successfully',
    });
    await expect(page.getByRole('cell', { name: planName })).toBeVisible();
  });

  // roast test createSubscriptionTest
  test('should create subscription @priority=critical @suite=nocode-P0-automation', async ({
    page,
  }) => {
    await page.getByRole('link', { name: 'Plans' }).click();
    await expect(page.getByRole('cell', { name: planName })).toBeVisible();
    await page.getByRole('link', { name: 'Subscriptions', exact: true }).click();
    await page.getByRole('link', { name: 'Create New Subscription' }).click();
    await page.locator('.PowerSelect__TriggerInputContainer').first().click();
    await page.getByText(planName).click();
    await page.getByText('Immediate, subscriptions starts with the first payment').click();
    await page.locator('input[name="total_count"]').fill('3');
    await page.getByRole('button', { name: 'Next' }).click();
    await page.getByRole('button', { name: 'Next' }).click();
    await page.getByText('No Expiry').click();
    await page.getByRole('button', { name: 'Next' }).click();
    await page.getByRole('button', { name: 'Create Subscription Link' }).click();

    await expectSuccessNotification({
      page,
      notificationText: 'Subscription Created Successfully',
    });

    subscriptionId = await page.evaluate(() => {
      const firstRowFirstCellLink = document.querySelector('tbody tr:first-child td:first-child a');
      return firstRowFirstCellLink.innerText;
    });
    await expect(
      page.getByRole('link', {
        name: subscriptionId,
      }),
    ).toBeVisible();
  });

  // roast test cancelSubscription
  test('should cancel subscription @priority=critical @suite=nocode-P0-automation', async ({
    page,
  }) => {
    await page.locator('input[name="id"]').fill(subscriptionId);
    await page.getByRole('button', { name: 'Search' }).click();
    await page.getByRole('link', { name: subscriptionId }).click();

    await page.getByRole('button', { name: 'Cancel' }).click();
    await page.getByText('Cancel Immediately').click();
    await page.getByRole('button', { name: 'Yes, Cancel' }).click();
  });
});
