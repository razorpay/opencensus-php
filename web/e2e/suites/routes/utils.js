import { routes } from 'testConstants';
export async function getPaymentId({ page }) {
  await page.goto(routes.TRANSACTIONS_PAYMENTS);
  const paymentRecordRow = await page.locator('.data-table').locator('tr').nth(0);
  const paymentID = await paymentRecordRow.$('a[href^="/app/payments/"]').getText();
  return paymentID ?? '';
}

export async function searchPaymentId({ page, paymentId }) {
  const searchBtn = await page.getByRole('button', { name: 'Search' });
  expect(searchBtn).toBeVisible();
  await page.locator('input[name="id"]').fill(paymentId);
  await searchBtn.click();
  await expect(await page.getByRole('cell', { name: paymentId })).toBeVisible();
  const paymentRecordRow = await page.$(`tr:has(td:has-text("${paymentId}"))`);
  if (!paymentRecordRow) {
    console.log(`Not able to find Payment Record with Id ${paymentId}`);
  }
}
