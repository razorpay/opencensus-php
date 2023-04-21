const { test, expect } = require('@playwright/test');
const { generateRandomEmail } = require('../utils');
const { routes } = require('../utils/constants');
const { StorageStatePath } = require('../utils/constants');

test.describe.parallel('My account and settings @flow=account-settings', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
    await page.getByRole('link', { name: 'Account & Settings' }).click();
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
  });

  test('should render merchant profile section', async ({ page }) => {
    const profileSection = page.locator('text=Your profile');
    await expect(profileSection).toBeVisible();
    const merchantId = page.locator('p:has-text("Merchant ID")');
    await expect(merchantId).toBeVisible();
    const copyMerchantId = page.locator('button[role="button"]:has-text("Copy")');
    await expect(copyMerchantId).toBeVisible();
    const twoStepVerification = page.locator('text=2-step verification');
    await expect(twoStepVerification).toBeVisible();
    const displayName = page.locator('p:has-text("Display Name")');
    await expect(displayName).toBeVisible();
    const phoneNumber = page.locator('p:has-text("Phone Number")');
    await expect(phoneNumber).toBeVisible();
    const loginEmail = page.locator('text=Login email');
    await expect(loginEmail).toBeVisible();
    const password = page.locator('text=Password');
    await expect(password).toBeVisible();
  });

  test('should render account and product settings section', async ({ page }) => {
    const accountAndProductSettings = page.locator('text=Account and product settings');
    await expect(accountAndProductSettings).toBeVisible();
    const paymentsAndRefundsSettings = page.locator('text=Payments and refunds');
    await expect(paymentsAndRefundsSettings).toBeVisible();
  });

  test.describe.parallel('Payments and refunds', () => {
    test('should render payment and refund settings', async ({ page }) => {
      await page.locator('button[role="button"]:has-text("Credits")').click();
      await expect(page).toHaveURL(RegExp(routes.CREDITS));
      await page.locator('[data-testid="breadcrumb"] >> text=Account & Settings').click();
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.locator('button[role="button"]:has-text("Balances")').click();
      await expect(page).toHaveURL(routes.BALANCES);
      // click on back arrow
      await page.locator('[data-testid="breadcrumb"] i').first().click();
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.locator('button[role="button"]:has-text("Reminders")').click();
      await expect(page).toHaveURL(routes.REMINDERS);
      await page.locator('[data-testid="breadcrumb"] >> text=Account & Settings').click();
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.locator('button[role="button"]:has-text("Transaction limits")').click();
      await expect(page).toHaveURL(routes.TRANSACTION_LIMITS);
      await page.locator('[data-testid="breadcrumb"] >> text=Account & Settings').click();
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.locator('button[role="button"]:has-text("Capture and refund settings")').click();
      await expect(page).toHaveURL(routes.CAPTURE_AND_REFUND_SETTINGS);
      await page.locator('[data-testid="breadcrumb"] >> text=Account & Settings').click();
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
    });

    test('should render Balances tab', async ({ page }) => {
      await page.locator('button[role="button"]:has-text("Balances")').click();
      await expect(page).toHaveURL(routes.BALANCES);
      await expect(page.locator('text=Documentation')).toHaveAttribute(
        'href',
        'https://razorpay.com/docs/payment-gateway/dashboard-guide/balances/',
      );
      await page.locator('text=Manage Alerts').click();
      await page.locator('input').first().fill('200');
      await page.locator('text=Save').click();
      await expect(
        page.locator('[data-testid="Notification--success"]', {
          hasText: 'Balance threshold updated successfully',
        }),
      ).toBeVisible();
      await expect(page.locator('text=Current Balance')).toBeVisible();
      await page.locator('button:has-text("Add Funds")').click();
      await page.locator('[placeholder="Enter Description"]').fill('Developer testing');
      await page.locator('[placeholder="Enter Amount\\(INR\\)"]').fill('1000');
      await page.locator('[aria-label="Modal"] button:has-text("Add Funds")').click();
      // Orders API always fails on devstack as the current account doesn't have access to API keys, thus checking for the failed state
      await expect(
        page.locator('[data-testid="Notification--error"]', {
          hasText: 'Authentication failed,Status Code: 400',
        }),
      ).toBeVisible();
      await expect(
        page.locator('text=Reserve Balance').filter({
          hasText: 'Add funds to your reserve balance to increase the negative balance limit.',
        }),
      ).toBeVisible();
    });

    test.describe.parallel('Credits tab', () => {
      test.beforeEach(async ({ page }) => {
        await page.locator('button[role="button"]:has-text("Credits")').click();
        await expect(page).toHaveURL(routes.CREDITS);
      });

      test('should render Credits tab', async ({ page }) => {
        await expect(page.locator('text=Amount Credits').first()).toBeVisible();
        await expect(page.locator('text=Fee Credits').first()).toBeVisible();
        await expect(page.locator('text=Refund Credits').first()).toBeVisible();
        await expect(page.locator('text=Documentation')).toHaveAttribute(
          'href',
          'https://razorpay.com/docs/payment-gateway/dashboard-guide/credits/',
        );
        await page.locator('text=Manage Alerts').click();
        await page.locator('text=Amount Credit₹₹₹ >> input').first().fill('100');
        await page.locator('text=Fee Credit₹₹₹ >> input').first().fill('12');
        await page.locator('text=Refund Credit₹₹₹ >> input').first().fill('14');
        await page.locator('text=Save').click();
        await expect(
          page.locator('[data-testid="Notification--success"]', {
            hasText: 'Credits threshold updated successfully',
          }),
        ).toBeVisible();
      });

      test('should show fee credits history modal @priority=normal', async ({ page }) => {
        const feeCreditsViewHistoryCTA = await page
          .getByRole('button', { name: 'View History' })
          .first();
        await expect(feeCreditsViewHistoryCTA).toBeVisible();
        await feeCreditsViewHistoryCTA.click();
        const feeCreditsHeader = await page.getByRole('heading', { name: 'Fee Credits History' });
        await expect(feeCreditsHeader).toBeVisible();
        await page.getByRole('button', { name: 'Close' }).click();
      });

      test('should show refund credits history modal @priority=normal', async ({ page }) => {
        const refundCreditsViewHistoryCTA = await page
          .getByRole('button', { name: 'View History' })
          .nth(1);
        await expect(refundCreditsViewHistoryCTA).toBeVisible();
        await refundCreditsViewHistoryCTA.click();
        const refundCreditsHeader = await page.getByRole('heading', {
          name: 'Refund Credits History',
        });
        await expect(refundCreditsHeader).toBeVisible();
        await page.getByRole('button', { name: 'Close' }).click();
      });
    });
  });
  test('should show manage team tab and send invite @priority=normal', async ({ page }) => {
    await page.getByRole('button', { name: 'Manage team' }).click();
    await expect(page).toHaveURL(routes.MANAGE_TEAM);
    const inviteCTA = await page.getByRole('button', { name: 'Invite New Member' });
    expect(inviteCTA).toBeVisible();
    await inviteCTA.click();
    const randomEmail = generateRandomEmail();
    await page.getByPlaceholder('Email').click();
    await page.getByPlaceholder('Email').fill(randomEmail);
    await page.getByRole('combobox').selectOption('support');
    await page.getByRole('button', { name: 'Send Invitation' }).click();
    await expect(
      page.locator('[data-testid="Notification--success"]', {
        hasText: `Invitation has been successfully sent to ${randomEmail}`,
      }),
    ).toBeVisible();
    const newInvite = await page.locator(`tr td:has-text("${randomEmail}")`);
    await expect(newInvite).toBeVisible();
  });
});
