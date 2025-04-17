import { routes, test, expect, getStorageStatePath } from '@libs/shared-qsuite/playwright';

const ELEMENT_CONFIG = {
  STORE_GROUPS_ACCORDION: 'text="Store Groups"',
  ADD_NEW_STORE_GROUP_BUTTON: 'button >> text="Add New Group"',
  ADD_STORE_GROUP_MODAL_TITLE: '[data-blade-component="text"]:has-text("Add New Group")',
  ADD_STORE_GROUP_MODAL_SUBMIT_BUTTON: 'button >> text="Add"',
  STORE_CHIP: '[data-blade-component="chip"]',
  STORE_GROUP_CARD: 'div[data-blade-component="card"]',
  EDIT_STORE_GROUP_BUTTON: 'button >> text="Edit Group"',
  EDIT_STORE_GROUP_MODAL_TITLE: '[data-blade-component="text"]:has-text("Edit Group")',
  EDIT_STORE_GROUP_MODAL_SUBMIT_BUTTON: 'button >> text="Save"',
  DELETE_STORE_GROUP_BUTTON: 'button >> text="Delete Group"',
  DELETE_STORE_GROUP_MODAL_MESSAGE:
    '[data-blade-component="text"]:has-text("Group once deleted cannot be recovered.")',
  STORE_GROUP_MODAL_CANCEL_BUTTON: 'button >> text="Cancel"',
  STORES_TABLE_COLUMN_NAME: '[data-blade-component="text"]:has-text("Store Name")',
  STORES_ONLINE_TYPE_FILTER_OPTION: 'button >> [data-blade-component="text"]:has-text("Online")',
  TABLE_LOADER: '[data-blade-component="spinner"] >> [aria-label="Refreshing Table"]',
  STORES_TABLE_ROW: '[data-blade-component="table-row"]',
  STORE_WITH_ONLINE_TYPE:
    '[data-blade-component="table-row"] >> [data-blade-component="badge"] >> [data-blade-component="text"]',
  STORE_WITH_TEST_STORE_NAME:
    '[data-blade-component="table-row"] >> [data-blade-component="base-text"]:has-text("Test Store")',
  STORE_DETAILS_OVERVIEW_TAB: 'text="Overview"',
  STORE_DETAILS_EDIT_BUTTON: 'button >> text="Edit"',
  STORE_DELETE_ALERT_MODAL: '[data-blade-component="text"]:has-text("Heads Up!")',
  DELETE_BUTTON: 'button >> text="Delete"',
  CANCEL_BUTTON: 'button >> text="Cancel"',
};

test.describe
  .parallel('Store Settings page @flow=store-management-settings @project=payments', () => {
    test.use({
      storageState: getStorageStatePath().ACTIVATED_RZP_MERCHANT,
    });

    test.beforeEach(async ({ page }) => {
      await page.goto(routes.STORE_SETTINGS);
      await expect(page.locator(ELEMENT_CONFIG.STORE_GROUPS_ACCORDION)).toBeVisible();
    });

    test('should be able to perform CRUD operations on Store Groups', async ({
      page,
    }) => {
      // New Store Group creation
      await page.locator(ELEMENT_CONFIG.ADD_NEW_STORE_GROUP_BUTTON).click();
      await expect(page.locator(ELEMENT_CONFIG.ADD_STORE_GROUP_MODAL_TITLE)).toBeVisible();

      // 'Add' button should be disabled when Store Group name is empty and when no Store is selected
      await expect(page.locator(ELEMENT_CONFIG.ADD_STORE_GROUP_MODAL_SUBMIT_BUTTON)).toBeDisabled();

      const storeGroupName = `Store Group - ${Date.now()} - ${Math.floor(Math.random() * 1000)}`;
      await page.getByPlaceholder('Enter Group Name').fill(storeGroupName);
      await page.getByPlaceholder('Enter Group Description').fill('Test Store Group Description');
      const storeChip = await page.locator(ELEMENT_CONFIG.STORE_CHIP).first();
      if (storeChip) {
        await storeChip.click();

        // 'Add' button should be enabled when Store Group name is not empty and when a Store is selected
        await expect(page.locator(ELEMENT_CONFIG.ADD_STORE_GROUP_MODAL_SUBMIT_BUTTON)).toBeEnabled();

        await page.locator(ELEMENT_CONFIG.ADD_STORE_GROUP_MODAL_SUBMIT_BUTTON).click();
        await expect(page.locator(ELEMENT_CONFIG.ADD_STORE_GROUP_MODAL_TITLE)).not.toBeVisible();

        // View and update newly created Store Group
        await page
          .locator(ELEMENT_CONFIG.STORE_GROUP_CARD)
          .filter({ hasText: storeGroupName })
          .nth(1)
          .click();
        await page.locator(ELEMENT_CONFIG.EDIT_STORE_GROUP_BUTTON).click();
        await expect(page.locator(ELEMENT_CONFIG.EDIT_STORE_GROUP_MODAL_TITLE)).toBeVisible();
        await expect(page.getByPlaceholder('Enter Group Name')).toHaveValue(storeGroupName);
        await page.locator(ELEMENT_CONFIG.STORE_GROUP_MODAL_CANCEL_BUTTON).click();
        await expect(page.locator(ELEMENT_CONFIG.ADD_STORE_GROUP_MODAL_TITLE)).not.toBeVisible();

        // Delete created Store Group
        await expect(await page.locator(ELEMENT_CONFIG.STORE_GROUP_CARD).filter({ hasText: storeGroupName }).nth(1)).toBeVisible();
        await page.locator(ELEMENT_CONFIG.DELETE_STORE_GROUP_BUTTON).click();
        await expect(page.locator(ELEMENT_CONFIG.DELETE_STORE_GROUP_MODAL_MESSAGE)).toBeVisible();
        await page.locator(ELEMENT_CONFIG.DELETE_BUTTON).click();
        await expect(page.locator(ELEMENT_CONFIG.DELETE_STORE_GROUP_MODAL_MESSAGE)).not.toBeVisible();
        await expect(page.locator(ELEMENT_CONFIG.DELETE_STORE_GROUP_BUTTON)).not.toBeVisible();
        await expect(await page.locator(ELEMENT_CONFIG.STORE_GROUP_CARD).filter({ hasText: storeGroupName }).nth(1)).not.toBeVisible();
      }
    });

    test('should list Stores with filters and search options', async ({
      page,
    }) => {
      await expect(page.locator(ELEMENT_CONFIG.STORES_TABLE_COLUMN_NAME)).toBeVisible();

      // Apply Store Type filter
      await page.getByPlaceholder('Select Store Type').click();
      await page.locator(ELEMENT_CONFIG.STORES_ONLINE_TYPE_FILTER_OPTION).click();

      // Apply search filter
      const searchField = await page.getByPlaceholder('Search', { exact: true });
      await searchField.fill('Test Store');
      await searchField.press('Enter');

      // Store Details page
      await expect(page.locator(ELEMENT_CONFIG.TABLE_LOADER)).not.toBeVisible();
      const storesCount = await page.locator(ELEMENT_CONFIG.STORES_TABLE_ROW).count();
      if (storesCount > 0) {
        expect(page.locator(ELEMENT_CONFIG.STORE_WITH_ONLINE_TYPE).first()).toHaveText('Online');
        await page.locator(ELEMENT_CONFIG.STORE_WITH_TEST_STORE_NAME).first().click();
        await expect(page.locator(ELEMENT_CONFIG.STORE_DETAILS_OVERVIEW_TAB)).toBeVisible();
        await expect(page.locator(ELEMENT_CONFIG.STORE_DETAILS_EDIT_BUTTON)).toBeVisible();

        // Delete Store
        await page.locator(ELEMENT_CONFIG.DELETE_BUTTON).click();
        await expect(page.locator(ELEMENT_CONFIG.STORE_DELETE_ALERT_MODAL)).toBeVisible();
        await page.locator(ELEMENT_CONFIG.CANCEL_BUTTON).click();
        await expect(page.locator(ELEMENT_CONFIG.STORE_DELETE_ALERT_MODAL)).not.toBeVisible();
      }
    });
  });
