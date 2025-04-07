import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

const ELEMENT_CONFIG = {
  STORE_BRANDS_TAB: 'text="Store Brands"',
  ADD_NEW_BRAND_BUTTON: 'button >> text="New Brand"',
  ADD_BRAND_MODAL_TITLE:
    '[data-blade-component="modal-header"] >> [data-blade-component="text"]:has-text("Add New Brand")',
  ADD_BRAND_MODAL_CANCEL_BUTTON: 'button >> text="Cancel"',
  BRAND_NAME_LINK: '[data-blade-component="link"]:has-text("Test Brand Name")',
  EDIT_BRAND_MODAL_TITLE: '[data-blade-component="text"]:has-text("Brand Details")',
  BRAND_NAME: '[data-blade-component="text"]:has-text("Test Brand Name")',
  BRAND_INFO_EDIT_OPTION: '[data-blade-component="base-text"]:has-text("Edit")',
  EDIT_BRAND_MODAL_SUBMIT_BUTTON: 'button >> text="Save"',
  BILLING_TERMINALS_TAB: 'text="Billing Terminals"',
  TERMINALS_TABLE_COLUMN_NAME: '[data-blade-component="text"]:has-text("POS Details")',
  TABLE_ROW: '[data-blade-component="table-row"]',
  TABLE_LOADER: '[data-blade-component="spinner"] >> [aria-label="Refreshing Table"]',
  TERMINAL_STATUS_TOGGLE_FIELD: '[data-blade-component="switch"]',
  TERMINAL_STATUS_TOGGLE_INPUT: '[data-blade-component="switch"] >> input',
  TERMINAL_STATUS_CHANGE_ALERT_MODAL: '[data-blade-component="text"]:has-text("Alert")',
  TERMINAL_STATUS_CHANGE_ACKNOWLEDGE_BUTTON: 'button >> text="OK"',
};

test.describe
  .parallel('BillMe Settings page @flow=digital-billing-settings @project=payments', () => {
  test.use({
    storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.BILLME_SETTINGS);
    await expect(page.locator(ELEMENT_CONFIG.STORE_BRANDS_TAB)).toBeVisible();
  });

  test('should be able to perform CRUD operations on Brand', async ({
    page,
  }) => {
    // Brand Form Validation
    await expect(page.locator(ELEMENT_CONFIG.TABLE_LOADER)).not.toBeVisible();
    await page.locator(ELEMENT_CONFIG.ADD_NEW_BRAND_BUTTON).click();
    await expect(page.locator(ELEMENT_CONFIG.ADD_BRAND_MODAL_TITLE)).toBeVisible();
    await page.getByPlaceholder('Enter Brand Name').fill('Test Brand Name');
    await page.getByPlaceholder('Enter Description').fill('Test Brand Description');
    await page.locator(ELEMENT_CONFIG.ADD_BRAND_MODAL_CANCEL_BUTTON).click();

    // Apply filters
    const searchField = await page.getByPlaceholder('Search by brand name');
    await searchField.fill('Test Brand Name');
    await searchField.press('Enter');
    await expect(page.locator(ELEMENT_CONFIG.TABLE_LOADER)).not.toBeVisible();

    const brandsCount = await page.locator(ELEMENT_CONFIG.TABLE_ROW).count();
    if (brandsCount > 0) {
      // Brand Info Modal
      await page.locator(ELEMENT_CONFIG.BRAND_NAME_LINK).first().click();
      await expect(page.locator(ELEMENT_CONFIG.EDIT_BRAND_MODAL_TITLE)).toBeVisible();
      expect(page.locator(ELEMENT_CONFIG.BRAND_NAME)).toBeVisible();

      // Update Brand Info
      await page.locator(ELEMENT_CONFIG.BRAND_INFO_EDIT_OPTION).click();
      await page.getByPlaceholder('Enter Description').fill('Test Brand Description');
      await page.locator(ELEMENT_CONFIG.EDIT_BRAND_MODAL_SUBMIT_BUTTON).click();
      await expect(page.locator(ELEMENT_CONFIG.TABLE_LOADER)).not.toBeVisible();
      await expect(page.locator(ELEMENT_CONFIG.EDIT_BRAND_MODAL_TITLE)).not.toBeVisible();
    }
  });

  test('should list Store Terminals with status update toggle option', async ({
    page,
  }) => {
    await page.locator(ELEMENT_CONFIG.BILLING_TERMINALS_TAB).click();
    await expect(page.locator(ELEMENT_CONFIG.TABLE_LOADER)).not.toBeVisible();
    await expect(page.locator(ELEMENT_CONFIG.TERMINALS_TABLE_COLUMN_NAME)).toBeVisible();

    const terminalsCount = await page.locator(ELEMENT_CONFIG.TABLE_ROW).count();
    if (terminalsCount > 0) {
      // Update and validate Terminal status
      const prevStatus = await page
        .locator(ELEMENT_CONFIG.TERMINAL_STATUS_TOGGLE_INPUT)
        .first()
        .isChecked();
      await page.locator(ELEMENT_CONFIG.TERMINAL_STATUS_TOGGLE_FIELD).first().click();

      // if 'prevStatus' is true, then acknowledge the alert modal to confirm toggling off the status
      if (prevStatus) {
        await expect(page.locator(ELEMENT_CONFIG.TERMINAL_STATUS_CHANGE_ALERT_MODAL)).toBeVisible();
        await page.locator(ELEMENT_CONFIG.TERMINAL_STATUS_CHANGE_ACKNOWLEDGE_BUTTON).click();
      }

      await expect(page.locator(ELEMENT_CONFIG.TABLE_LOADER)).not.toBeVisible();
      await expect(
        page.locator(ELEMENT_CONFIG.TERMINAL_STATUS_CHANGE_ALERT_MODAL),
      ).not.toBeVisible();
      const updatedStatus = await page
        .locator(ELEMENT_CONFIG.TERMINAL_STATUS_TOGGLE_INPUT)
        .first()
        .isChecked();
      await expect(updatedStatus).not.toEqual(prevStatus);
    }
  });
});
