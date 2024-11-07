import { test, expect } from '@playwright/test';
import { getStorageStatePath, BASE_PATH, routes } from 'testConstants';
import {
  gotoOfferCreationFlow,
  disableOffer,
  getOfferName,
  fillDescription,
  selectOfferType,
  selectPaymentMethod,
  fillFlatDiscountType,
  fillOfferValidity,
  openDetailsPanel,
  fillPercentageDiscountType,
} from './utils/helper';

async function clickNext(page) {
  await page.getByRole('button', { name: 'Next' }).click();
}

test.describe
  .parallel('Offers creation Flow (Payment methods) @suite=merchant-offers @project=offers @skip-unstable', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).ACTIVATED_RZP_MERCHANT,
  });

  test.beforeEach(async ({ page }) => {
    await page.goto(routes.OFFERS_HOME);
    await expect(page).toHaveURL(routes.OFFERS_HOME);
  });

  // TO-DO: Skipping since granular offer experiment needs to be enabled
  test.skip('Granular Offers as payment method with selected payer account types and all upi apps', async ({
    page,
  }) => {});
  test.skip('Granular Offers as payment method with all payer account types and upi apps', async ({
    page,
  }) => {});

  test('Card as payment method', async ({ page }) => {
    // Validating for iin field
    const iins = '333666, f23201, 012000';

    await gotoOfferCreationFlow({ page });

    const offerName = getOfferName();

    // Description tab
    await fillDescription({ page, offerName });
    await clickNext(page);

    // Applicable on tab
    await selectOfferType({ page, offerType: 'Cashback' });
    await selectPaymentMethod({ page, paymentMethod: 'Card' });
    await page.getByPlaceholder('Max times a card can be used to avail this offer').fill('1');
    await page.locator("input[name='iins']").fill(iins);
    await clickNext(page);

    // Discount tab
    await fillFlatDiscountType({ page });
    await clickNext(page);

    // Offer validity tab
    await fillOfferValidity({ page });
    await clickNext(page);

    // Select agreement
    await page.getByText('I understand').click();
    await page.getByText(/create offer/i).click();

    // Cleanup, remove offer so as to avoid violating uniqueness
    // constraint by API.
    await expect(page.getByText(offerName)).toBeVisible();
    await openDetailsPanel({ page, offerName });
    await disableOffer({ page });
  });
  test('Net Banking as payment method', async ({ page }) => {
    await gotoOfferCreationFlow({ page });

    const offerName = getOfferName();

    // Description tab
    await fillDescription({ page, offerName });
    await clickNext(page);

    // Applicable on tab
    await selectOfferType({ page, offerType: 'Cashback' });
    await selectPaymentMethod({ page, paymentMethod: 'Net Banking' });
    await clickNext(page);

    // Discount tab
    await fillFlatDiscountType({ page });
    await clickNext(page);

    // Offer validity tab
    await fillOfferValidity({ page });
    await clickNext(page);

    // Select agreement
    await page.getByText('I understand').click();
    await page.getByText(/create offer/i).click();

    // Cleanup, remove offer so as to avoid violating uniqueness
    // constraint by API.
    await expect(page.getByText(offerName)).toBeVisible();
    await openDetailsPanel({ page, offerName });
    await disableOffer({ page });
  });

  test('Wallet as payment method', async ({ page }) => {
    await gotoOfferCreationFlow({ page });

    const offerName = getOfferName();

    // Description tab
    await fillDescription({ page, offerName });
    await clickNext(page);

    // Applicable on tab
    await selectOfferType({ page, offerType: 'Cashback' });
    await page.locator("button[name='payment_method']").click();
    await page.getByTestId('option-wallet').click();
    await clickNext(page);

    // Discount tab
    await fillFlatDiscountType({ page });
    await clickNext(page);

    // Offer validity tab
    await fillOfferValidity({ page });
    await clickNext(page);

    // Select agreement
    await page.getByText('I understand').click();
    await page.getByText(/create offer/i).click();

    // Cleanup, remove offer so as to avoid violating uniqueness
    // constraint by API.
    await expect(page.getByText(offerName)).toBeVisible();
    await openDetailsPanel({ page, offerName });
    await disableOffer({ page });
  });

  test('EMI as payment method', async ({ page }) => {
    await gotoOfferCreationFlow({ page });

    const offerName = getOfferName();

    // Description tab
    await fillDescription({ page, offerName });
    await clickNext(page);

    // Applicable on tab
    await selectOfferType({ page, offerType: 'Cashback' });
    await selectPaymentMethod({ page, paymentMethod: 'EMI' });
    await clickNext(page);

    // Discount tab
    await fillFlatDiscountType({ page });
    await clickNext(page);

    // Offer validity tab
    await fillOfferValidity({ page });
    await clickNext(page);

    // Select agreement
    await page.getByText('I understand').click();
    await page.getByText(/create offer/i).click();

    // Cleanup, remove offer so as to avoid violating uniqueness
    // constraint by API.
    await expect(page.getByText(offerName)).toBeVisible();
    await openDetailsPanel({ page, offerName });
    await disableOffer({ page });
  });

  test('Pay later as payment method and percentage as discount type.', async ({ page }) => {
    await gotoOfferCreationFlow({ page });

    const offerName = getOfferName();

    // Description tab
    await fillDescription({ page, offerName });
    await clickNext(page);

    // Applicable on tab
    await selectOfferType({ page, offerType: 'Cashback' });
    await selectPaymentMethod({ page, paymentMethod: 'Pay Later' });
    await clickNext(page);

    // Discount tab
    await fillPercentageDiscountType({ page });
    await clickNext(page);

    // Offer validity tab
    await fillOfferValidity({ page });
    await clickNext(page);

    // Select agreement
    await page.getByText('I understand').click();
    await page.getByText(/create offer/i).click();

    // Cleanup, remove offer so as to avoid violating uniqueness
    // constraint by API.
    await expect(page.getByText(offerName)).toBeVisible();
    await openDetailsPanel({ page, offerName });
    await disableOffer({ page });
  });

  test('Cardless EMI as payment method and Already discounted as offer type', async ({ page }) => {
    await gotoOfferCreationFlow({ page });

    const offerName = getOfferName();

    // Description tab
    await fillDescription({ page, offerName });
    await clickNext(page);

    // Applicable on tab
    await selectOfferType({ page, offerType: 'Cashback' });
    await selectPaymentMethod({ page, paymentMethod: 'Cardless EMI' });
    await clickNext(page);

    // Discount tab
    await fillPercentageDiscountType({ page });
    await clickNext(page);

    // Offer validity tab
    await fillOfferValidity({ page });
    await clickNext(page);

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
