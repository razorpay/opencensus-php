import React from 'react';

import CartPanel from 'apps/pos/src/app/views/SelfServe/Cart/CartPanel';
import { MOCK_USER } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { ProductPlans, PosDeviceStoreState } from 'apps/pos/src/app/views/SelfServe/types';
import { render, screen, userEvent, waitForElementToBeRemoved, server, waitFor } from 'test-utils';

describe('<CartPanel />', () => {
  const renderCartPanel = ({ cartItems }) => {
    const initialState = {
      ...(jest.requireActual('apps/pos/src/app/views/SelfServe/context').initialState as Record<
        string,
        string
      >),
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
    await waitFor(() => {
      expect(screen.getByText('Your Cart')).toBeVisible();
    });
    await waitFor(() => {
      expect(screen.getByText('1 Item')).toBeVisible();
      expect(screen.getByText('Mock Product')).toBeVisible();
    });
  });

  test('should render empty state if empty cart', async () => {
    renderCartPanel({ cartItems: [] });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByTestId('cart-button'));
    await waitFor(() => {
      expect(screen.getByText('Your cart is empty')).toBeVisible();
      expect(screen.getByText('Looks like you haven’t made your choices yet.')).toBeVisible();
      expect(screen.getByText('Shop Now')).toBeVisible();
    });
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
