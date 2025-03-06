import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

test.describe('It should create and update wallet campaign ', () => {
  test.use({
    storageState: getStorageStatePath().WALLET_MERCHANT_LOGIN_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.WALLET_CAMPAIGNS);
    await expect(page).toHaveURL(routes.WALLET_CAMPAIGNS);
  });

  test('should successfully create a campaign', async ({ page }) => {
    // Click 'New Campaign' button
    await page.getByRole('button', { name: /new campaign/i }).click();

    // Enter campaign name
    await page.getByLabel('Name').fill('Festive Cashback');

    // Click 'Create Campaign' button
    await page.getByRole('button', { name: /create campaign/i }).click();

    // Verify navigation to the campaign creation screen
    await expect(page).toHaveURL(
      '/app/wallet/campaigns/new?campaignName=Festive%20Cashback&campaignType=trigger',
    );

    // Select event
    await page.getByPlaceholder('Select event').click();
    await page.getByText('wallet_credit').click();

    // Select Action
    await page.getByRole('link', { name: 'Action' }).click();
    await page.getByPlaceholder('Select action').click();
    await page.getByText('Credit Wallet').click();

    // Select wallet and enter credit amount
    await page.getByPlaceholder('Select wallet').click();
    await page.getByText('Wallet1').click();
    await page.getByLabel('Credit Amount').fill('100');
    await page.getByText('Next').click();

    // Fill and submit burn rules
    await page.getByPlaceholder('Enter Duration').fill('10');
    await page.getByPlaceholder('Select Duration').click();
    await page.getByText('Days').click();
    await page.getByPlaceholder('Enter Value').fill('100');
    await page.getByText('Next').click();

    // Campaign settings
    await page.getByText('Start Immediately').click();
    await page.getByText('No End Date').click();
    await page.getByText('Next').click();

    // Publish the campaign
    await page.getByText('Publish Campaign').click();
    await expect(
      page.getByText('Please review your campaign rules carefully before publishing it.'),
    ).toBeVisible();

    // Click the submit button
    const submitButton = await page.waitForSelector('button[type="submit"]', { state: 'attached' });
    await page.waitForFunction(
      (btn) => btn.getAttribute('form') === 'campaign-creation-form',
      submitButton,
    );
    await submitButton.click();

    await expect(page.getByText('Campaign Created!')).toBeVisible();
  });

  test('should pause the latest campaign', async ({ page }) => {
    // Find the latest campaign and click the pause button
    const latestCampaignRow = await page.locator('table tbody tr').first();
    await latestCampaignRow.locator('button[aria-label="pause"]').click();

    //Click confirmation button
    await page.getByText('Pause Campaign').click();

    // Confirm the campaign is paused
    await expect(page.getByText('Campaign updated successfully')).toBeVisible();
  });
});
