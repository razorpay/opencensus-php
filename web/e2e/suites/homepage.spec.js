import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

const HOMEPAGE_SELECTORS = {
  analytics: {
    dropdown: '#analytics-daterange-picker >> .PowerSelect__Trigger',
    dropdownLabel: '#analytics-daterange-picker >> .PowerSelect__TriggerLabel',
  },
  keyMetrics: {
    dropdown: '#keymetrics-grouping >> .PowerSelect__Trigger',
    dropdownLabel: '#keymetrics-grouping >> .PowerSelect__TriggerLabel',
    volumeOption: '.PowerSelect__Option[data-option-index="0"]',
    paymentmethodOption: '.PowerSelect__Option[data-option-index="1"]',
  },
};

test.describe
  .parallel('Test dashboard landing page @flow=home @project=payments @project=payments-roast', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });
  test.beforeEach(async ({ page }) => {
    await page.goto(routes.DASHBOARD);
  });
  test('should show heading when visiting home page @priority=normal', async ({ page }) => {
    // Expect a title "to contain" a substring.
    await expect(page).toHaveTitle(/Razorpay Dashboard/);

    // const datePicker = page.locator('#analytics-daterange-picker');
    // await expect(datePicker).toBeVisible();
  });

  // roast test selectAndVerifyCalenderDropdownTest
  test('should show days intervals in analytics chart @suite=payments-automation', async ({
    page,
  }) => {
    await page.waitForSelector(HOMEPAGE_SELECTORS.analytics.dropdown, {
      state: 'visible',
    });
    const calenderDropdownBtn = await page.locator(HOMEPAGE_SELECTORS.analytics.dropdown);
    await expect(calenderDropdownBtn).toBeVisible();

    const verifyDropDownOption = async ({ ctaText, ctaIndex, value }) => {
      await calenderDropdownBtn.click();
      await page.locator(`[data-option-index="${ctaIndex}"]`).filter({ hasText: ctaText }).click();
      const selectedOption = await page
        .locator(HOMEPAGE_SELECTORS.analytics.dropdownLabel)
        .innerText();
      expect(selectedOption).toEqual(ctaText);

      const startDateText = await page.getByPlaceholder('Start Date').inputValue();
      const endDateText = await page.getByPlaceholder('End Date').inputValue();
      const startDate = new Date(startDateText);
      const endDate = new Date(endDateText);

      const timeDiff = Math.abs(endDate.getTime() - startDate.getTime());
      const diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));
      expect(diffDays).toBe(value);
    };

    await verifyDropDownOption({
      ctaIndex: 0,
      ctaText: 'Past 7 Days',
      value: 7,
    });

    await verifyDropDownOption({
      ctaIndex: 1,
      ctaText: 'Past 30 Days',
      value: 30,
    });

    await verifyDropDownOption({
      ctaIndex: 2,
      ctaText: 'Past 90 Days',
      value: 90,
    });
  });

  // roast test verifySettlementLinkTest
  test('should show settlement link and redirect to settlement page @suite=payments-automation', async ({
    page,
  }) => {
    const settlementRedirectCTA = page.getByRole('link', { name: 'View Settlements' });
    await expect(settlementRedirectCTA).toBeVisible();
    await settlementRedirectCTA.click();
    await expect(page).toHaveURL(routes.SETTLEMENTS);
  });

  // roast test clickAndVerifyByTotalVolumeDropdownTest
  test('should show total volume dropdown @suite=payments-automation', async ({ page }) => {
    const dropDown = await page.locator(HOMEPAGE_SELECTORS.keyMetrics.dropdown);
    await expect(dropDown).toBeVisible();

    await dropDown.click();
    const paymentMethodOption = await page.locator(
      HOMEPAGE_SELECTORS.keyMetrics.paymentmethodOption,
    );
    await expect(paymentMethodOption).toBeVisible();
    await paymentMethodOption.click();
    const dropDownLabel = await page.locator(HOMEPAGE_SELECTORS.keyMetrics.dropdownLabel);
    await expect(await dropDownLabel.innerText()).toBe('By Payment Method');

    await dropDown.click();
    const paymentvolumeOption = await page.locator(HOMEPAGE_SELECTORS.keyMetrics.volumeOption);
    await expect(paymentvolumeOption).toBeVisible();
    await paymentvolumeOption.click();
    await expect(await dropDownLabel.innerText()).toBe('By Total Volume');
  });
});
