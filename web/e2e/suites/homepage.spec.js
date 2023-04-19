const { test, expect } = require('@playwright/test');
const { StorageStatePath, routes } = require('../utils/constants');

test.describe.parallel('Test dashboard landing page @flow=home', () => {
  test.use({
    storageState: StorageStatePath.EMAIL_TEST_LOGIN_STATE,
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
  test('should show days intervals in analytics chart', async ({ page }) => {
    const calenderDropdownBtn = await page.locator(
      "//div[@class='presets-container pull-left']//span[@class='PowerSelect__TriggerStatus']",
    );
    await expect(calenderDropdownBtn).toBeVisible();

    const verifyDropDownOption = async ({ ctaText, ctaIndex, value }) => {
      await calenderDropdownBtn.click();
      await page.locator(`[data-option-index="${ctaIndex}"]`).filter({ hasText: ctaText }).click();
      const selectedOption = await page
        .locator(
          "//div[@class='rzp-daterange-picker clearfix']//descendant::div[@class='PowerSelect__Trigger']/div",
        )
        .textContent();
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

  test('should show settlement link and redirect to settlement page', async ({ page }) => {
    const settlementRedirectCTA = page.getByRole('link', { name: 'View Settlements' });
    await expect(settlementRedirectCTA).toBeVisible();
    await settlementRedirectCTA.click();
    await expect(page).toHaveURL(routes.SETTLEMENTS);
  });

  test('should show total volume dropdown', async ({ page }) => {
    const dropDown = await page
      .locator(
        '.grouping-dropdown > .rzp-group > div:nth-child(2) > .PowerSelect > .PowerSelect__Trigger > .PowerSelect__TriggerLabel',
      )
      .first();
    await expect(dropDown).toBeVisible();

    const dropDownLabel = await page.locator(
      "//div[@class='PowerSelect react-normal-select PowerSelect--focused']//div[@class='PowerSelect__TriggerLabel']",
    );
    expect(dropDownLabel).toBeVisible();

    await dropDown.click();
    const paymentMethodOption = await page.locator('.PowerSelect__Options > div:nth-child(2)');
    await expect(paymentMethodOption).toBeVisible();
    await paymentMethodOption.click();
    await expect(await dropDownLabel.innerText()).toBe('By Payment Method');

    await dropDown.click();
    const paymentvolumeOption = await page.locator('.PowerSelect__Options > div:nth-child(1)');
    await expect(paymentvolumeOption).toBeVisible();
    await paymentvolumeOption.click();
    await expect(await dropDownLabel.innerText()).toBe('By Total Volume');
  });
});
