import React from 'react';

import Cart from 'merchant/views/POS/Cart/CartPanel/Cart';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosStoreInitialState } from 'merchant/views/POS/constants';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { ProductPlans, PosDeviceStoreState } from 'merchant/views/POS/types';
import { render, screen, server, userEvent, waitForElementToBeRemoved, within } from 'test-utils';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

const renderApp = (isSideModal = false) => {
  const MOCK_CART_ITEMS = [
    {
      code: 'mock-product',
      quantity: 3,
      plan: 'monthly' as ProductPlans,
    },
    {
      code: 'mock-product',
      quantity: 3,
      plan: 'lifetime' as ProductPlans,
    },
    {
      code: 'mock-product-new',
      quantity: 3,
      plan: 'lifetime' as ProductPlans,
    },
  ];

  const initialState = {
    ...PosStoreInitialState,
    cartItems: MOCK_CART_ITEMS,
  };

  render(
    <PosDeviceStoreProvider init={initialState as PosDeviceStoreState} user={MOCK_USER}>
      <Cart isCartModal={isSideModal} />
    </PosDeviceStoreProvider>,
  );
};

describe('<Cart/>', () => {
  beforeEach(async () => {
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });

  test('should render Cart on screen', () => {
    expect(screen.getByText('Mock Product New')).toBeVisible();
  });

  test('should toggle plans on clicking on pricing cards', async () => {
    const cartItemEl = screen.getByTestId('mock-product-new-lifetime-cart-item');
    const subscriptionCard = within(cartItemEl).getByText('Monthly Plan').parentNode as HTMLElement;
    await userEvent.click(subscriptionCard);
    const newCartItemEl = screen.getByTestId('mock-product-new-monthly-cart-item');
    expect(
      within(newCartItemEl).getByText('Monthly Plan').parentNode as HTMLElement,
    ).toHaveAttribute('aria-selected', 'true');
  });

  test('should merge pricing cards if same plans selected', async () => {
    const cartItemEl = screen.getByTestId('mock-product-lifetime-cart-item');
    const subscriptionCard = within(cartItemEl).getByText('Monthly Plan').parentNode as HTMLElement;
    await userEvent.click(subscriptionCard);
    const subsCartItemEl = screen.getByTestId('mock-product-monthly-cart-item');
    expect(within(subsCartItemEl).getByTestId('quantity-value')).toHaveTextContent('6');
    expect(cartItemEl).not.toBeInTheDocument();
  });

  test('should increase quantity if clicked on increase', async () => {
    const cartItemEl = screen.getByTestId('mock-product-monthly-cart-item');
    await userEvent.click(within(cartItemEl).getByLabelText('increase cart quantity'));
    expect(
      within(screen.getByTestId('mock-product-monthly-cart-item')).getByTestId('quantity-value'),
    ).toHaveTextContent('4');
  });

  test('should increase quantity if clicked on increase', async () => {
    const cartItemEl = screen.getByTestId('mock-product-monthly-cart-item');
    await userEvent.click(within(cartItemEl).getByLabelText('reduce cart quantity'));
    expect(
      within(screen.getByTestId('mock-product-monthly-cart-item')).getByTestId('quantity-value'),
    ).toHaveTextContent('2');
  });

  test('should delete cart item if clicked on remove icon', async () => {
    const cartItemEl = screen.getByTestId('mock-product-monthly-cart-item');
    await userEvent.click(within(cartItemEl).getByLabelText('product delete icon'));
    expect(screen.queryByTestId('mock-product-monthly-cart-item')).toBeNull();
  });
});

describe('Cart as a modal', () => {
  beforeEach(async () => {
    const isCartModal = true;
    server.use(getProductPricingHandler());
    renderApp(isCartModal);
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });

  test('should call track_EXPERIMENTAL events on clicking on pricing cards', async () => {
    const cartItemEl = screen.getByTestId('mock-product-new-lifetime-cart-item');
    const subscriptionCard = within(cartItemEl).getByText('Monthly Plan');
    await userEvent.click(subscriptionCard);
    expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.websiteCtaClicked, {
      label: 'Monthly Subscription',
      whatsAppUpdates: 'No',
      section: 'Cart',
      subSection: 'Mock Product New',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Cart',
    });
  });

  test('should call track_EXPERIMENTAL event on click on increase', async () => {
    const cartItemEl = screen.getByTestId('mock-product-monthly-cart-item');
    await userEvent.click(within(cartItemEl).getByLabelText('increase cart quantity'));
    expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.iconClicked, {
      type: 'Add',
      noOfItems: 3,
      section: 'Cart',
      subSection: 'Mock Product',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Cart',
    });
  });

  test('should call track_EXPERIMENTAL event if clicked on remove icon', async () => {
    const cartItemEl = screen.getByTestId('mock-product-monthly-cart-item');
    await userEvent.click(within(cartItemEl).getByLabelText('product delete icon'));
    expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.iconClicked, {
      type: 'Delete Icon',
      noOfItems: 3,
      section: 'Cart',
      subSection: 'Mock Product',
      l1FunnelStage: 'Purchase Intention',
      l2FunnelStage: 'Cart',
    });
  });
});
