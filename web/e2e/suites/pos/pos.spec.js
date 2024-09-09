const { routes, getStorageStatePath, BASE_PATH } = require('testConstants');
const { test, expect } = require('utils/base');
const { navigateTo } = require('utils/common');

const { MAIN_BANNER_TEXT_CONTENT, PDP_CONTENT, DEVICE_CODES } = require('./constants');
const { waitForPosCatalogToLoad } = require('./utils');

test.describe.parallel('POS Device Store @flow=pos-device-ordering @project=payments', () => {
  test.use({
    storageState: getStorageStatePath(BASE_PATH).POS_LOGIN_STATE,
  });
  test.describe('POS Catalog Page', () => {
    test('should open pos catalog page when clicked on pos sidebar item', async ({ page }) => {
      await navigateTo(page, routes.DASHBOARD);
      await page.getByRole('link', { name: 'POS' }).click();
      await waitForPosCatalogToLoad({ page });
      await expect(page).toHaveURL(`${routes.POS}/catalog`);
    });

    test('should show products on screen and on click should add to cart @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });
      const mainBanner = page.getByTestId('main-banner-wrapper');
      MAIN_BANNER_TEXT_CONTENT.forEach(async (content) => {
        await expect(mainBanner).toContainText(content);
      });
      await expect(
        page
          .getByTestId('android-mini-pos-product-card')
          .getByText('Android Smart Mini POS', { exact: true }),
      ).toBeVisible();

      await expect(
        page
          .getByTestId('android-mini-pos-product-card')
          .getByText('Limited Time Offer till 31st May'),
      ).toBeVisible();

      await expect(page.getByText('Feature packed and portable')).toBeVisible();
      const mobilePosProductCart = page.getByTestId('mobile-pos-product-card');
      await expect(mobilePosProductCart).toContainText('Mobile POS (mPOS)');
      await expect(page.getByText('Pocket-sized and affordable')).toBeVisible();
    });

    test('should add device from catalog to cart and cart should be visible with actions @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });

      const androidSmartPosAddToCartBtn = page.getByTestId('main-banner-wrapper').getByText('Add');
      await androidSmartPosAddToCartBtn.click();

      const cartItem = page.getByTestId(`${DEVICE_CODES.androidSmartPos}-monthly-cart-item`);
      await expect(cartItem).toBeVisible();
      await expect(cartItem.getByTestId('quantity-value')).toHaveText('1');
      await cartItem.getByLabel('increase cart quantity').click();
      await expect(cartItem.getByTestId('quantity-value')).toHaveText('2');
      await cartItem.getByLabel('reduce cart quantity').click();
      await expect(cartItem.getByTestId('quantity-value')).toHaveText('1');
      await page.getByLabel('product delete icon').click();
      expect(cartItem).not.toBeVisible();
      await page.getByLabel('cart close button').click();

      const mobilePos = page.getByTestId('mobile-pos-product-card');
      await mobilePos.getByText('Add to cart').click();
      const mobilePosCartItem = page.getByTestId(`${DEVICE_CODES.mobilePos}-monthly-cart-item`);
      //TODO:: cart item plan change assertion
      await expect(mobilePosCartItem.getByTestId('quantity-value')).toHaveText('1');
      await page.getByLabel('cart close button').click();

      const miniPos = page.getByTestId('android-mini-pos-product-card');
      await miniPos.getByText('Add to cart').click();
      const miniPosCartItem = page.getByTestId(`${DEVICE_CODES.androidMiniPos}-monthly-cart-item`);
      await expect(miniPosCartItem.getByTestId('quantity-value')).toHaveText('1');
    });
  });

  test.describe('POS Product Description ', () => {
    test('should render PDP on screen with content @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });

      const mainBanner = page.getByTestId('main-banner-wrapper');
      await mainBanner.getByText('Learn More').click();
      await expect(
        page.getByText(PDP_CONTENT[DEVICE_CODES.androidSmartPos].subtitle),
      ).toBeVisible();
      await expect(page.getByTestId('pdp-title')).toHaveText(
        PDP_CONTENT[DEVICE_CODES.androidSmartPos].title,
      );
      await expect(page.getByText('Limited Time Offer till 31st May')).toBeVisible();

      await page.getByText('Catalog').click();

      const mobilePos = page.getByTestId('mobile-pos-product-card');
      await mobilePos.getByText('Learn More').click();
      await expect(page.getByText(PDP_CONTENT[DEVICE_CODES.mobilePos].subtitle)).toBeVisible();
      await expect(page.getByTestId('pdp-title')).toHaveText(
        PDP_CONTENT[DEVICE_CODES.mobilePos].title,
      );
      await page.getByText('Catalog').click();

      const miniPos = page.getByTestId('android-mini-pos-product-card');
      await miniPos.getByText('Learn More').click();
      await expect(page.getByText(PDP_CONTENT[DEVICE_CODES.androidMiniPos].subtitle)).toBeVisible();
      await expect(page.getByTestId('pdp-title')).toHaveText(
        PDP_CONTENT[DEVICE_CODES.androidMiniPos].title,
      );
    });

    test('should be able to add products in cart and should reflect in the cart @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });

      const mainBanner = page.getByTestId('main-banner-wrapper');
      await mainBanner.getByText('Learn More').click();
      await expect(
        page.getByText(PDP_CONTENT[DEVICE_CODES.androidSmartPos].subtitle),
      ).toBeVisible();
      await page.getByPlaceholder('Enter PIN Code').fill('560034');
      await page.getByRole('button', { name: 'Check', exact: true }).click();
      await page.waitForSelector(`text=Delivery in 2-3 business days post KYC approval.`);
      const pdpactions = page.getByTestId('pdp-actions');
      await pdpactions.getByText('Add to cart').click();
      await expect(pdpactions.getByTestId('quantity-value')).toHaveText('1');
      await page.getByTestId('lifetime-price-card').click();
      await pdpactions.getByText('Add to cart').click();
      await page.getByText('Proceed to Order').click();
      await expect(page.getByTestId('order-summary-container')).toContainText('2 Items');
      await expect(page.getByTestId('order-summary-container')).toContainText('Lifetime Plan');
      await expect(page.getByTestId('order-summary-container')).toContainText('Monthly Plan');
    });
  });

  test.describe('POS Order Summary', () => {
    test('should redirect to order summary if clicked on proceed to cart and should be able to modify order in order summary @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });

      const androidSmartPosAddToCartBtn = page.getByTestId('main-banner-wrapper').getByText('Add');
      await androidSmartPosAddToCartBtn.click();

      const cartItem = page.getByTestId(`${DEVICE_CODES.androidSmartPos}-monthly-cart-item`);
      await expect(cartItem).toBeVisible();
      await expect(cartItem.getByTestId('quantity-value')).toHaveText('1');
      await page.getByText('Place Order').click();
      await expect(page.getByText('Order Summary')).toBeVisible();
      const orderSummary = page.getByTestId('order-summary-container');
      await orderSummary.getByText('Edit').click();
      await orderSummary.getByLabel('increase cart quantity').click();
      await expect(orderSummary.getByTestId('quantity-value')).toHaveText('2');
      await orderSummary.click('Done');
    });

    test('should render order pricing details on scren @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });

      const androidSmartPosAddToCartBtn = page.getByTestId('main-banner-wrapper').getByText('Add');
      await androidSmartPosAddToCartBtn.click();

      await page.getByTestId('pos-cart-overlay').click();

      const mposAddToCartBtn = page.getByTestId('mobile-pos-product-card').getByText('Add');
      await mposAddToCartBtn.click();

      const cartItem = page.getByTestId(`${DEVICE_CODES.androidSmartPos}-monthly-cart-item`);
      await expect(cartItem).toBeVisible();
      await expect(cartItem.getByTestId('quantity-value')).toHaveText('1');
      await page.getByText('Place Order').click();
      await expect(page.getByText('Order Summary')).toBeVisible();
      await expect(page.getByText('Payment Details')).toBeVisible();
      await page.getByText('Device charges').click();
      const deviceCharges = page.getByTestId('device-charges-container');
      await deviceCharges.click();
      await expect(deviceCharges.getByText('Android Smart POS')).toBeVisible();
      await expect(deviceCharges.getByText('Monthly Plan | (Qty: 1)')).toBeVisible();

      await expect(
        deviceCharges.getByText('Mobile POS (mPOS) | Monthly Plan (Qty: 1)'),
      ).toBeVisible();

      await expect(page.getByText('Shipping')).toBeVisible();
      await expect(page.getByText('Total Order Price')).toBeVisible();
      const rentalCharges = page.getByTestId('rental-charges-container');
      await rentalCharges.getByText('Rental charges').click();
      await expect(
        rentalCharges.getByText('Monthly Plan - Android Smart POS X 1').nth(1),
      ).toBeVisible();

      await expect(
        rentalCharges.getByText('Mobile POS (mPOS) | Monthly Plan (Qty: 1)'),
      ).toBeVisible();

      await expect(rentalCharges.getByText('first 3 months')).toBeVisible();
      await expect(rentalCharges.getByText('post 3 months')).toBeVisible();
      await expect(rentalCharges.getByText('MDR (%)')).toBeVisible();

      await expect(page.getByText('Renewal')).toBeVisible();
    });

    test('should be able to fill new address and proceed with checkout @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });

      const androidSmartPosAddToCartBtn = page.getByTestId('main-banner-wrapper').getByText('Add');
      await androidSmartPosAddToCartBtn.click();
      await page.getByText('Place Order').click();
      await expect(page.getByText('Order Summary')).toBeVisible();
      const deliveryAddressContainer = page.getByTestId('delivery-address-container');
      await deliveryAddressContainer.getByText('Add New Address').click();
      await deliveryAddressContainer.getByPlaceholder('Enter Name').fill('Test Name');
      await deliveryAddressContainer.getByPlaceholder('Enter Mobile Number').fill('8486467098');
      await deliveryAddressContainer.getByPlaceholder('Enter Pincode').fill('560034');
      await deliveryAddressContainer.getByPlaceholder('Enter City').fill('Bengaluru');
      await deliveryAddressContainer.getByPlaceholder('Select a state').click();
      await page.getByTestId('Karnataka-option').click();
      await deliveryAddressContainer.getByPlaceholder('Enter Address').fill('Test Address');
      await deliveryAddressContainer.getByText('Save Address').click();
      await page.waitForSelector('text=Test Name, Test Address, Bengaluru');
      await page.getByText('Confirm Address & Pay').isEnabled();
    });

    test('should persist cart and devlivery address upon refresh  @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });

      const androidSmartPosAddToCartBtn = page.getByTestId('main-banner-wrapper').getByText('Add');
      await androidSmartPosAddToCartBtn.click();
      await page.getByText('Place Order').click();
      await expect(page.getByText('Order Summary')).toBeVisible();
      const deliveryAddressContainer = page.getByTestId('delivery-address-container');
      await deliveryAddressContainer.getByText('Add New Address').click();
      await deliveryAddressContainer.getByPlaceholder('Enter Name').fill('Test Name');
      await deliveryAddressContainer.getByPlaceholder('Enter Mobile Number').fill('8486467098');
      await deliveryAddressContainer.getByPlaceholder('Enter Pincode').fill('560034');
      await deliveryAddressContainer.getByPlaceholder('Enter City').fill('Bengaluru');
      await deliveryAddressContainer.getByPlaceholder('Select a state').click();
      await page.getByTestId('Karnataka-option').click();
      await deliveryAddressContainer.getByPlaceholder('Enter Address').fill('Test Address');
      await deliveryAddressContainer.getByText('Save Address').click();

      await page.reload();
      await page.waitForSelector('text=Test Name, Test Address, Bengaluru');
      await expect(
        page.getByTestId(`${DEVICE_CODES.androidSmartPos}-monthly-order-item`),
      ).toBeVisible();
    });

    test('should open confirm checkout prompt if amount is 0  @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });

      const androidSmartPosAddToCartBtn = page.getByTestId('main-banner-wrapper').getByText('Add');
      await androidSmartPosAddToCartBtn.click();

      const cartItem = page.getByTestId(`${DEVICE_CODES.androidSmartPos}-monthly-cart-item`);
      await expect(cartItem).toBeVisible();
      await expect(cartItem.getByTestId('quantity-value')).toHaveText('1');
      await page.getByText('Place Order').click();

      await page.getByText('Confirm Address & Pay').click();
      await expect(
        page.getByText('By clicking on Confirm, your order will be placed!'),
      ).toBeVisible();
    });
  });

  test.describe('Order Listing and Details without order', () => {
    test('should show empty order screen if no orders @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await page.waitForSelector('text=Orders');
      await page.getByText('Orders').click();
      await expect(page.getByText('No Order History')).toBeVisible();
      await page.getByText('Shop now').click();
      await expect(
        page
          .getByTestId('android-mini-pos-product-card')
          .getByText('Android Smart Mini POS', { exact: true }),
      ).toBeVisible();
    });
  });
});

test.describe.parallel(
  'POS Device Store Order Details with order @flow=pos-device-ordering @project=payments',
  () => {
    test.use({
      storageState: getStorageStatePath(BASE_PATH).POS_ORDER_DETAILS_LOGIN_STATE,
    });

    test.skip('should render order listing screen for ordered items @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });
      await page.getByText('Orders').click();
      await page.waitForSelector('text=Your Orders');
      await expect(page.getByText('Arriving by')).toBeVisible();
      await expect(page.getByText('ORDER RECEIVED')).toBeVisible();
      await page.getByText('View Order Details').click();
      await page.waitForSelector('text=Arriving by');
      const orderStatustimeline = page.getByTestId('order-status-timeline-container');
      await expect(orderStatustimeline.getByText('Order Received')).toBeVisible();
      await expect(orderStatustimeline.getByText('Order Confirmed')).toBeVisible();
      await expect(orderStatustimeline.getByText('Delivered')).toBeVisible();
      await expect(page.getByText('Shipping Address')).toBeVisible();
      await expect(page.getByText('POS merchant')).toBeVisible();
      await expect(page.getByText('razorpay sjr, adugodi, Bengaluru, KA-560066')).toBeVisible();

      const merchantContactContainer = page.getByTestId('merchant-contact-container');
      await expect(merchantContactContainer.getByText('+913999233214')).toBeVisible();
      await expect(merchantContactContainer.getByText('omnitest@gmail.com')).toBeVisible();
    });

    test.skip('should show order confirmation screen with confirmation content @flow=pos-device-ordering @project=pos-onboarding', async ({
      page,
    }) => {
      await navigateTo(page, routes.POS);
      await waitForPosCatalogToLoad({ page });
      await page.getByText('Orders').click();
      await page.waitForSelector('text=Your Orders');
      await navigateTo(page, `/app/pos/order-status/NBrJW3mPg4xfGY`);
      await page.waitForSelector('text=Your order is successfully placed!');
      await expect(page.getByText('NBrJW3mPg4xfGY')).toBeVisible();
      await expect(page.getByText('has successfully been placed with us')).toBeVisible();
      await page.getByText('View Orders').click();
      await page.waitForSelector('text=Your Orders');
    });
  },
);
