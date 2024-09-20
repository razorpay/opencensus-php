import { expect } from '@playwright/test';

export async function gotoOfferCreationFlow({ page }) {
  await page.getByRole('button', { name: /create new offer/i }).click();
  await page.getByText(/discounts & cash backs/i).click();
}

export async function openDetailsPanel({ page, offerName }) {
  const firstrow = await page.locator("div[data-blade-component='table'] tr:first-child");
  expect(firstrow.getByText(offerName)).toBeVisible();
  await firstrow.locator('a').click();
}

export async function disableOffer({ page }) {
  const modal = await page.locator('.Offers--Details');

  await expect(modal.getByText('Enabled')).toBeVisible();
  await modal.getByText('Disable').click();
  await page.getByText('yes, disable').click();
}

export function getOfferName() {
  return 'TestOffer' + Math.floor(new Date().getTime() / 1000);
}

export async function fillDescription({ page, offerName }) {
  if (!offerName) throw new Error('Offername not provided while creating offer.');
  await Promise.all([
    await page
      .getByPlaceholder('Example: New Year Sale (This name appears on your dashboard)')
      .fill(offerName),
    await page
      .getByPlaceholder(
        '10% off on all HDFC Debit Cards (This appears on checkout for your customers)',
      )
      .fill('Dummy offer for Razorpay E2E testing.'),
    await page
      .getByPlaceholder('Terms and conditions for offer')
      .fill('Dummy Offer: valid only in E2E suite of Razorpay'),
  ]);
}

export async function selectOfferType({ page, offerType = 'Cashback' }) {
  await page.locator("button[name='type']").click();
  await page.getByText(offerType, { exact: true }).click();
}

export async function selectPaymentMethod({ page, paymentMethod = 'Card' }) {
  await page.locator("button[name='payment_method']").click();
  await page.getByText(paymentMethod, { exact: true }).click();
}

export async function fillFlatDiscountType({ page }) {
  let minAmount = 10 + (Math.random() * 10000) / 100;
  let cashback = Math.max(1, Math.random() * minAmount);

  await page.locator("button[name='discount_type']").click();
  await page.getByText(/flat/i).click();
  await page.locator("[name='min_amount']").fill(minAmount.toFixed(2));
  await page.locator("[name='flat_cashback']").fill(cashback.toFixed(2));
}

export async function fillPercentageDiscountType({ page }) {
  const discountPercentage = Math.max(1, Math.random() * 100);
  const maxDiscount = Math.max(1, Math.random() * 10000);

  await page.locator("button[name='discount_type']").click();
  await page.getByText(/percentage/i).click();
  await page.locator("[name='percent_rate']").fill(discountPercentage.toFixed(2));
  await page.locator("[name='max_cashback']").fill(maxDiscount.toFixed(2));
}

export async function fillOfferValidity({ page }) {
  await page.locator(`input[placeholder="DD-MM-YYYY"]:not([disabled])`).click();
  await page.locator('.rc-calendar-tbody [aria-selected=true]').click();
  await page.locator("button[name='block']").click();
  await page.getByText(/do not allow payment to go through/i).click();
}
