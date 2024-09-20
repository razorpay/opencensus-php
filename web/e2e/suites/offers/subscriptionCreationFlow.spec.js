import { test, expect } from '@playwright/test';
import { getStorageStatePath, BASE_PATH, routes } from 'testConstants';
import {
  gotoOfferCreationFlow,
  fillDescription,
  getOfferName,
  selectOfferType,
  selectPaymentMethod,
  fillPercentageDiscountType,
  fillFlatDiscountType,
  fillOfferValidity,
  openDetailsPanel,
  disableOffer,
} from './utils/helper';
import { SELECTORS } from './utils/constants';

// NOTE: Different Payment Method aren't tested as they are covered in
// Offer subscription flows.
test.describe
  .parallel('[Subscription] Offer creation flow @suite=merchant-offers @project=offers', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.OFFERS_HOME);
    await expect(page).toHaveURL(routes.OFFERS_HOME);
  });

  test('Happy subscription offer creation flow.', async ({ page }) => {
    // Go to Subscription Offer Form
    await page.getByRole('button', { name: /create new offer/i }).click();
    await page.getByText(/offers on subscriptions/i).click();

    const offerName = getOfferName();

    // Description tab
    await fillDescription({ page, offerName });
    await page.getByText(SELECTORS.NEXT_CTA).click();

    // Discount tab
    await fillPercentageDiscountType({ page });
    await page.getByText(SELECTORS.NEXT_CTA).click();

    // Applicable on tab
    await selectPaymentMethod({ page, paymentMethod: 'Card' });
    await page.getByText(SELECTORS.NEXT_CTA).click();

    // Offer validity tab
    await fillOfferValidity({ page });
    await page.getByText(SELECTORS.NEXT_CTA).click();

    // Select agreement
    await page.getByText('I understand').click();
    await page.getByText(SELECTORS.CREATE_OFFER_CTA).click();

    // Cleanup, remove offer so as to avoid violating uniqueness
    // constraint by API.
    await expect(page.getByText(offerName)).toBeVisible();
    await openDetailsPanel({ page, offerName });
    await disableOffer({ page });
  });

  test('Percentage as discount type and limited cycles as redemption type.', async ({ page }) => {
    // Go to Subscription Offer Form
    await page.getByRole('button', { name: /create new offer/i }).click();
    await page.getByText(/offers on subscriptions/i).click();

    const offerName = getOfferName();

    // Description tab
    await fillDescription({ page, offerName });
    await page.getByText(/next/i).click();

    // Discount tab
    await page.locator("button[name='redemption_type']").click();
    await page.getByText('Limited Number of Cycles').click();
    await page.locator("input[name='no_of_cycles']").fill('3');
    await fillPercentageDiscountType({ page });
    await page.getByText(/next/i).click();

    // Applicable on tab
    await selectPaymentMethod({ page, paymentMethod: 'Card' });
    await page.getByText(/next/i).click();

    // Offer validity tab
    await fillOfferValidity({ page });
    await page.getByText(/next/i).click();

    // Select agreement
    await page.getByText('I understand').click();
    await page.getByText(/create offer/i).click();

    // Cleanup, remove offer so as to avoid violating uniqueness
    // constraint by API.
    await expect(page.getByText(offerName)).toBeVisible();
    await openDetailsPanel({ page, offerName });
    await disableOffer({ page });
  });
});
