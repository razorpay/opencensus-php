import {
  expect,
  generateRandomText,
  getRandomCustomerData,
  getRandomItemData,
} from '@libs/shared-qsuite/playwright';

export const createNewCustomer = async ({ page }) => {
  const { email, name, phone } = getRandomCustomerData();
  await page.getByPlaceholder('Select a customer').press('ArrowDown');
  await page.getByText('Create New Customer').click();
  await page.getByPlaceholder('Customer Name').fill(name);
  await page.getByPlaceholder('Email Address').fill(email);
  await page.getByPlaceholder('Contact Number').fill(phone);
  await page.getByRole('button', { name: 'Create Customer' }).click();
  return name;
};

export const createNewItemInInvoice = async ({ page }) => {
  const { name, amount } = getRandomItemData();
  await page.getByPlaceholder('Select an item').press('ArrowDown');
  await page.getByText('Create new Item').click();
  await page.getByPlaceholder('Item Name').fill(name);
  await page.getByPlaceholder('Amount').fill(amount);
  await page.getByRole('button', { name: 'Add Item' }).click();
  return name;
};

export const createNewItem = async ({ page }) => {
  const { name, amount } = getRandomItemData();
  await page.getByText('New Item').click();
  await page.getByPlaceholder('Item Name').fill(name);
  await page.getByPlaceholder('Amount').fill(amount);
  await page.getByRole('button', { name: 'Save' }).click();
  return name;
};

export const createInvoice = async ({ page, invoiceData }) => {
  const referenceId = generateRandomText(12);
  await page.getByText('Create Invoice').click();
  await page.getByRole('button', { name: 'Continue to Invoice' }).click();
  await page.locator('input[name="receipt"]').fill(referenceId);
  await page.getByPlaceholder('Enter a Brief Description or Summary').fill(invoiceData.description);
  const customerName = await createNewCustomer({ page });
  await expect(await page.getByText(customerName)).toBeVisible();
  const itemName = await createNewItemInInvoice({ page });
  const itemNameInput = await page.getByPlaceholder('Select an item');
  await expect(itemNameInput).toHaveValue(itemName);
  await page.getByText('Finalize and Issue').click();
  await page.getByRole('button', { name: 'Issue Invoice' }).click();
  return referenceId;
};

export const searchInvoiceAndOpenDetails = async ({ page, referenceId }) => {
  // TODO: Adding 30s due to elastic search issue. Needs to be rectified.
  await page.waitForTimeout(30 * 1000);
  const searchBtn = await page.getByRole('button', { name: 'Search' });
  expect(searchBtn).toBeVisible();
  await searchBtn.click();
  await page.locator('input[name="receipt"]').fill(referenceId);
  await searchBtn.click();
  await expect(await page.getByRole('cell', { name: referenceId })).toBeVisible();
  const invoiceRecordRow = await page.$(`tr:has(td:has-text("${referenceId}"))`);
  if (!invoiceRecordRow) {
    console.log(`Not able to find invoice Record with reference Id ${referenceId}`);
    return;
  }
  const linkElement = await invoiceRecordRow.$('a[href^="/app/invoices/"]');
  if (!linkElement) {
    console.log(
      `Not able to find link element for invoice Record with reference Id ${referenceId}`,
    );
    return;
  }
  await linkElement.click();
};
