import React from 'react';

import CartItem from 'apps/pos/src/app/views/SelfServe/Cart/CartPanel/CartItem';
import {
  MOCK_USER,
  MOCK_PRODUCT_OFFER_CONFIG,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import * as posHelpers from 'apps/pos/src/app/views/SelfServe/helpers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { ProductPlans, PosDeviceStoreState } from 'apps/pos/src/app/views/SelfServe/types';
import { render, screen, server, waitForElementToBeRemoved } from 'test-utils';

const renderApp = () => {
  const MOCK_CART_ITEM = {
    code: 'mock-product',
    quantity: 3,
    plan: 'monthly' as ProductPlans,
  };

  const initialState = {
    ...(jest.requireMock('apps/pos/src/app/views/SelfServe/context').initialState as Record<
      string,
      string
    >),
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

describe('CartItem component', () => {
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

describe('CartItem component with offer', () => {
  beforeEach(async () => {
    const fetchOffersSpy = jest.spyOn(posHelpers, 'fetchProductOffers');
    fetchOffersSpy.mockReturnValue({
      isEnabled: true,
      offers: MOCK_PRODUCT_OFFER_CONFIG,
    });
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });

  test('should render pricing on screen once fetched', () => {
    expect(screen.getByTestId('pricing-monthly-offer-0')).toHaveTextContent(
      '0 rental first 3 months',
    );
    expect(screen.getByTestId('pricing-monthly-offer-1')).toHaveTextContent('400');
    expect(screen.getByText(/12,000/)).toBeVisible();
    expect(screen.getByText(/20,000/)).toBeVisible();
    expect(screen.getByText(/rental after 3 months/)).toBeVisible();
    expect(screen.getByText(/rental first 3 months/)).toBeVisible();
  });
  test('should render quantity widget on screen', () => {
    expect(screen.getByText('Quantity')).toBeVisible();
    expect(screen.getByLabelText('reduce cart quantity')).toBeVisible();
    expect(screen.getByLabelText('increase cart quantity')).toBeVisible();
  });
});
