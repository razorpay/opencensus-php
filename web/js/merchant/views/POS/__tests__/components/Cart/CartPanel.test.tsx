import React from 'react';

import CartPanel from 'merchant/views/POS/Cart/CartPanel';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { ProductPlans, PosDeviceStoreState } from 'merchant/views/POS/types';
import { render, screen, userEvent, waitForElementToBeRemoved, server, waitFor } from 'test-utils';

describe('<CartPanel />', () => {
  const renderCartPanel = ({ cartItems }) => {
    const initialState = {
      ...(jest.requireActual('merchant/views/POS/context').initialState as Record<string, string>),
      cartItems: cartItems ?? [
        {
          code: 'mock-product',
          quantity: 3,
          plan: 'monthly' as ProductPlans,
        },
      ],
    };

    render(
      <PosDeviceStoreProvider init={initialState as PosDeviceStoreState} user={MOCK_USER}>
        <CartPanel />
      </PosDeviceStoreProvider>,
    );
  };

  beforeEach(() => {
    server.use(getProductPricingHandler());
  });

  test('should render cart button with counter on screen', async () => {
    renderCartPanel({ cartItems: null });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByTestId('cart-button')).toBeVisible();
    expect(screen.getByTestId('cart-panel-counter')).toHaveTextContent('1');
  });

  test('should render cart on clicking on cart button', async () => {
    renderCartPanel({ cartItems: null });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByTestId('cart-button'));
    expect(screen.getByText('Your Cart')).toBeVisible();
    expect(screen.getByText('1 Item')).toBeVisible();
    expect(screen.getByText('Mock Product')).toBeVisible();
  });

  test('should render  empty state if empty cart', async () => {
    renderCartPanel({ cartItems: [] });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByTestId('cart-button'));
    expect(screen.getByText('Your cart is empty')).toBeVisible();
    expect(screen.getByText('Looks like you haven’t made your choices yet.')).toBeVisible();
    expect(screen.getByText('Shop Now')).toBeVisible();
  });

  test('should render max limit reached error if more than orderable limit', async () => {
    renderCartPanel({
      cartItems: [
        {
          code: 'mock-product',
          quantity: 11,
          plan: 'monthly' as ProductPlans,
        },
        {
          code: 'mock-product',
          quantity: 11,
          plan: 'lifetime' as ProductPlans,
        },
      ],
    });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByTestId('cart-button'));
    await waitFor(() => {
      expect(screen.getByText('This order can accommodate a maximum of 9 items')).toBeVisible();
    });
    expect(screen.getByText('2 Items')).toBeVisible();
    expect(screen.getByText('Reduce the total number of items for this order')).toBeVisible();
    expect(screen.getByTestId('place-order-btn')).toBeDisabled();
  });
});
