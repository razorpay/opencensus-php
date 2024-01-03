import React from 'react';

import CartItem from 'merchant/views/POS/Cart/CartPanel/CartItem';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { ProductPlans, PosDeviceStoreState } from 'merchant/views/POS/types';
import { render, screen, server, waitForElementToBeRemoved } from 'test-utils';

describe('<CartItem/>', () => {
  const renderApp = () => {
    const MOCK_CART_ITEM = {
      code: 'mock-product',
      quantity: 3,
      plan: 'monthly' as ProductPlans,
    };

    const initialState = {
      ...(jest.requireMock('merchant/views/POS/context').initialState as Record<string, string>),
      cartItems: [MOCK_CART_ITEM],
    };
    const initProps = {
      onPricingUpdate: jest.fn(),
      onProductRemove: jest.fn(),
      onProductQuantityUpdate: jest.fn(),
    };
    render(
      <PosDeviceStoreProvider init={initialState as PosDeviceStoreState} user={MOCK_USER}>
        <CartItem cartItem={MOCK_CART_ITEM} {...initProps} />
      </PosDeviceStoreProvider>,
    );
  };

  beforeEach(async () => {
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });

  test('should render cart item on screen', () => {
    expect(screen.getByText('Mock Product')).toBeVisible();
  });
  test('should render pricing cards on screen', () => {
    expect(screen.getByText('Monthly Plan')).toBeVisible();
    expect(screen.getByText('Lifetime Plan')).toBeVisible();
  });

  test('should render pricing on screen once fetched', () => {
    expect(screen.getByText(/300/)).toBeVisible();
    expect(screen.getByText(/12,000/)).toBeVisible();
  });
  test('should render quantity widget on screen', () => {
    expect(screen.getByText('Quantity')).toBeVisible();
    expect(screen.getByLabelText('reduce cart quantity')).toBeVisible();
    expect(screen.getByLabelText('increase cart quantity')).toBeVisible();
  });
});
