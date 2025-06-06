import {
  routes,
  test,
  expect,
  getStorageStatePath,
  COMMON_SELECTORS,
  expectSuccessNotification,
  generateRandomEmail,
  mouseClickToggleSwitch,
  waitAndProceedVerificationPopup,
} from '@libs/shared-qsuite/playwright';

test.describe
  .parallel('My account and settings @flow=account-settings @project=payments @project=payments-roast', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.ACCOUNT_SETTINGS);
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
  });

  test('should navigate to Account and Settings Page on CTA click', async ({ page }) => {
    // click on Account and settings link in sidebar
    await page.goto(routes.DASHBOARD);
    await page.getByRole('link', { name: 'Account & Settings' }).click();
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
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

    // roast test myAccountAddFundsTest
    test('should render Balances tab @suite=payments-canary', async ({ page }) => {
      await page.locator('button[role="button"]:has-text("Balances")').click();
      await expect(page).toHaveURL(routes.BALANCES);
      await expect(page.getByRole('link', { name: 'Documentation', exact: true })).toHaveAttribute(
        'href',
        'https://razorpay.com/docs/payment-gateway/dashboard-guide/balances/',
      );
      await page.locator('text=Manage Alerts').click();
      await page.locator('input').first().fill('200');
      await page.locator('text=Save').click();
      await expectSuccessNotification({
        page,
        notificationText: 'Balance threshold updated successfully',
      });
      await expect(page.locator('text=Current Balance')).toBeVisible();

      // flow has changed will be covered separately
      // await page.locator('button:has-text("Add Funds")').click();
      // await page.locator('[placeholder="Enter Description"]').fill('Developer testing');
      // await page.locator('[placeholder="Enter Amount\\(INR\\)"]').fill('1000');
      // await page.locator('[aria-label="Modal"] button:has-text("Add Funds")').click();
      // // Orders API always fails on devstack as the current account doesn't have access to API keys, thus checking for the failed state
      // await expect(
      //   page.locator('[data-testid="Notification--error"]', {
      //     hasText: 'Authentication failed,Status Code: 400',
      //   }),
      // ).toBeVisible();
      // await expect(
      //   page.locator('text=Reserve Balance').filter({
      //     hasText: 'Add funds to your reserve balance to increase the negative balance limit.',
      //   }),
      // ).toBeVisible();
    });

    test.describe.parallel('Credits tab', () => {
      test.beforeEach(async ({ page }) => {
        await page.locator('button[role="button"]:has-text("Credits")').click();
        await expect(page).toHaveURL(routes.CREDITS);
      });

      // roast test myAccountCreditsTest
      test('should render Credits tab @suite=payments-automation @suite=payments-canary', async ({
        page,
      }) => {
        await expect(page.locator('text=Amount Credits').first()).toBeVisible();
        await expect(page.locator('text=Fee Credits').first()).toBeVisible();
        await expect(page.locator('text=Refund Credits').first()).toBeVisible();
        await expect(
          page.getByRole('link', { name: 'Documentation', exact: true }),
        ).toHaveAttribute(
          'href',
          'https://razorpay.com/docs/payment-gateway/dashboard-guide/credits/',
        );
        await expect(async () => {
          await page.locator('text=Manage Alerts').click();
          await page.locator('text=Amount Credit₹₹₹ >> input').first().fill('100');
          await page.locator('text=Fee Credit₹₹₹ >> input').first().fill('12');
          await page.locator('text=Refund Credit₹₹₹ >> input').first().fill('14');
          await page.locator('text=Save').click();
          await expectSuccessNotification({
            page,
            notificationText: 'Credits threshold updated successfully',
          });
        }).toPass({ timeout: 30000, intervals: [1000, 5000, 10000] });
      });

      // roast test myAccountFeeCreditsHistoryTest
      test('should show fee credits history modal @priority=normal @suite=payments-automation @suite=payments-canary', async ({
        page,
      }) => {
        const feeCreditsViewHistoryCTA = await page
          .getByRole('button', { name: 'View History' })
          .first();
        await expect(feeCreditsViewHistoryCTA).toBeVisible();
        await feeCreditsViewHistoryCTA.click();
        const feeCreditsHeader = await page.getByRole('heading', { name: 'Fee Credits History' });
        await expect(feeCreditsHeader).toBeVisible();
        await page.getByRole('button', { name: 'Close' }).click();
      });

      // roast test myAccountRefundCreditsHistoryTest
      test('should show refund credits history modal @priority=normal @suite=payments-automation @suite=payments-canary', async ({
        page,
      }) => {
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

  // roast test myAccountManageTeamTest
  test.skip('should show manage team tab and send invite @priority=normal @suite=payments-automation @suite=payments-canary', async ({
    page,
  }) => {
    await page.getByRole('button', { name: 'Manage team' }).click();
    await expect(page).toHaveURL(routes.MANAGE_TEAM);
    const inviteCTA = await page.getByRole('button', { name: 'Invite New Member' });
    expect(inviteCTA).toBeVisible();
    await inviteCTA.click();
    await waitAndProceedVerificationPopup({ page });
    const randomEmail = generateRandomEmail();
    await page.getByPlaceholder('Email').click();
    await page.getByPlaceholder('Email').fill(randomEmail);
    await page.getByRole('combobox').selectOption('support');
    await expect(async () => {
      await page.getByRole('button', { name: 'Send Invitation' }).click();
      await expectSuccessNotification({
        page,
        notificationText: `Invitation has been successfully sent to ${randomEmail}`,
      });
      const newInviteSelector = `tr td:has-text("${randomEmail}")`;
      await page.waitForSelector(newInviteSelector, {
        strict: false,
        state: 'visible',
      });
      const newInvite = await page.locator(newInviteSelector);
      await expect(newInvite).toBeVisible();
    }).toPass({ timeout: 30000, intervals: [1000, 5000, 10000] });
  });

  // roast test myAccountActivationTest
  test('should show gst details @priority=normal @suite=payments-automation @suite=payments-canary', async ({
    page,
  }) => {
    await page.getByRole('button', { name: 'GST details' }).click();
    await expect(page).toHaveURL(routes.GST_DETAILS);

    // get to details container and assert presence of GST details of merchant and rzp

    await expect(
      page.getByText('GST Number', {
        exact: true,
      }),
    ).toBeVisible();
    await expect(page.getByText('26AADCS0472N1Z4')).toBeVisible();

    await expect(
      page.getByText("Razorpay's GST Number", {
        exact: true,
      }),
    ).toBeVisible();
    await expect(page.getByText('29AAGCR4375J1ZU')).toBeVisible();
  });

  // roast test smsNotificationTest
  test('should show sms notifications switch @priority=normal @suite=payments-automation @suite=payments-canary', async ({
    page,
  }) => {
    await page.getByRole('button', { name: 'SMS' }).click();
    await expect(page).toHaveURL(routes.SMS_NOTIFICATIONS);

    const container = await page.locator('.tabbed-container');
    // get switch toggle button, toggle it and assert notification for change success
    const switchKnob = await container.locator(COMMON_SELECTORS.toggleSwitch);
    const switchStatus = await container.locator('b.text-primary, b.text-faded');
    expect(switchKnob).toBeVisible();
    expect(switchStatus).toBeVisible();

    // api to fetch the status takes some time therefore
    await page.waitForTimeout(3000);

    const switchStatusValue = await switchStatus.textContent();

    await expect(async () => {
      await mouseClickToggleSwitch({ page, container });
      await expectSuccessNotification({
        page,
        notificationText: 'Your SMS preference was saved',
      });
      const updatedStatus = await switchStatus.textContent();
      // if earlier switch was enabled it should now be disabled and vice-versa
      expect(updatedStatus).toBe(switchStatusValue === 'Enabled' ? 'Disabled' : 'Enabled');
    }).toPass({ timeout: 30000, intervals: [1000, 5000, 10000] });
  });

  // roast test myAccountProfileTest
  test('should render business details section @suite=payments-canary', async ({ page }) => {
    await page.getByRole('button', { name: 'Business Details' }).click();
    await expect(page).toHaveURL(routes.BUSINESS_DETAILS);

    const businessDetailsSection = await page.getByTestId('business-details-section');
    await expect(businessDetailsSection).toBeVisible();

    await expect(await businessDetailsSection.getByText('Business Name')).toBeVisible();
    await expect(await businessDetailsSection.getByText('Test Ssab T')).toBeVisible();
  });

  test('should be able to view bank account details @priority=P0', async ({ page }) => {
    await page.getByRole('button', { name: 'Bank account details' }).click();
    await expect(await page.getByText('Beneficiary name')).toBeVisible();
    await expect(await page.getByText('Test', { exact: true })).toBeVisible();
    await expect(await page.getByText('Account number')).toBeVisible();
    await expect(await page.getByText('1232432424422442')).toBeVisible();
    await expect(await page.getByText('IFSC code')).toBeVisible();
    await expect(await page.getByText('SBIN0018176')).toBeVisible();
  });

  test('should be able to view activation details @priority=P0', async ({ page }) => {
    await page.getByRole('button', { name: 'Activation details' }).click();
    await expect(page.locator('text=Account Activation')).toBeVisible();
    const viewActivationFormCTA = page.getByRole('link', { name: 'View Activation Form' });
    await expect(viewActivationFormCTA).toBeVisible();
    viewActivationFormCTA.click();
    await expect(page.locator('text=KYC Form')).toBeVisible();
    const closeModalIcon = page.getByText('×');
    await closeModalIcon.waitFor({ state: 'visible' });
    await closeModalIcon.click();
    const activationDetailsUrl = page.url();
    await expect(activationDetailsUrl).toContain(routes.ACTIVATION_DETAILS);
  });

  test('should be able to add/update display name @priority=P0', async ({ page }) => {
    await page.getByRole('button', { name: 'Account details', exact: true }).click();
    await expect(page.locator('text=Display Name')).toBeVisible();
    const editDisplayNameCTA = page.getByTestId('display_name');
    await expect(editDisplayNameCTA).toBeVisible();
    editDisplayNameCTA.click();
    await expect(page.locator('text=Enter new display name')).toBeVisible();
    const displayNameInput = page.getByLabel('Enter new display');
    await displayNameInput.click();
    await displayNameInput.fill('Hello');
    await page.getByRole('button', { name: 'Update' }).click();
    await expectSuccessNotification({
      page,
      notificationText: 'Display name changed successfully.',
    });
    await expect(await page.getByText('Hello')).toBeVisible();
  });

  test('should be able to view team members with their role and pending invitations @priority=P0', async ({
    page,
  }) => {
    await page.getByRole('button', { name: 'Manage team', exact: true }).click();
    await expect(page.locator('text=Pending Invitations')).toBeVisible();
    await expect(page.locator('text=akash.rainadefw@razorpay.com')).toBeVisible();
    const elementCount = await page.locator('text=Manager').count();
    expect(elementCount).toBeGreaterThan(0);
    const teamMemberHeading = page.locator('text=Team Members').last();
    await expect(teamMemberHeading).toBeVisible();
    await expect(page.locator('text=Test SSABssab+5@rzp.com')).toBeVisible();
    await expect(page.locator('text=8888888516')).toBeVisible();
  });

  test.describe.parallel('Rewards & Streak tile - Test Mode', () => {
    test('do not render Rewards & Streak tile', async ({ page }) => {
      // check the streak locator do not exists
      const StringButton = await page.getByRole('button', { name: 'Streaks' });
      await expect(StringButton).toHaveCount(0);
    });
  });
});
test.describe
  .parallel('My account and settings @flow=account-settings @project=payments @project=payments-roast - Live Mode', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.ACCOUNT_SETTINGS);
    await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
  });

  test.describe.parallel('Rewards & Streak tile  - Live Mode', () => {
    test('should render Rewards & Streak tile', async ({ page }) => {
      // initial click
      await page.getByRole('button', { name: 'Streaks' }).click();
      await expect(page).toHaveURL(routes.STREAKS_REWARDS);
      // click on back arrow
      await page.locator('[data-testid="breadcrumb"] i').first().click();
      await expect(page).toHaveURL(routes.ACCOUNT_SETTINGS);
      await page.getByRole('button', { name: 'Streaks' }).click();
      await expect(page).toHaveURL(routes.STREAKS_REWARDS);
    });
  });
});
