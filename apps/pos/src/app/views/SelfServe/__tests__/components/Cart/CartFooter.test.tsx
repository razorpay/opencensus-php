import React from 'react';

import CartFooter from 'apps/pos/src/app/views/SelfServe/Cart/CartPanel/CartFooter';
import { MOCK_USER } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { PosDeviceStoreState, ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';
import { render, screen, server, waitForElementToBeRemoved } from 'test-utils';

const renderApp = ({ cartItems, isMaxReached }) => {
  const MOCK_CART_ITEMS = [
    {
      code: 'mock-product',
      quantity: 3,
      plan: 'monthly' as ProductPlans,
    },
    ...(cartItems ?? []),
  ];

  const initialState = {
    ...(jest.requireActual('apps/pos/src/app/views/SelfServe/context').initialState as Record<
      string,
      string
    >),
    cartItems: MOCK_CART_ITEMS,
  };
  render(
    <PosDeviceStoreProvider init={initialState as PosDeviceStoreState} user={MOCK_USER}>
      <CartFooter isMaxReached={isMaxReached} />
    </PosDeviceStoreProvider>,
  );
};

describe('<CartFooter/>', () => {
  beforeEach(() => {
    server.use(getProductPricingHandler());
  });

  test('should render cart footer on screen with correct total', async () => {
    renderApp({ cartItems: [], isMaxReached: false });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('600')).toBeVisible();
    expect(screen.getByText('Not inclusive of tax')).toBeVisible();
    expect(screen.getByText('Place Order')).toBeVisible();
  });

  test('should block place order CTA and show alert if total number of items exceeds max', async () => {
    renderApp({
      cartItems: [
        {
          code: 'mock-product',
          quantity: 9,
          plan: 'monthly' as ProductPlans,
        },
      ],
      isMaxReached: true,
    });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('This order can accommodate a maximum of 9 items')).toBeVisible();
    expect(screen.getByText('Reduce the total number of items for this order')).toBeVisible();
    expect(screen.getByRole('button')).toBeDisabled();
  });
});
