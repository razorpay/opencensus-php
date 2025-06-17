import React from 'react';

import OrderItem from 'apps/pos/src/app/views/SelfServe/OrderSummary/OrderItems/OrderItem';
import { MOCK_USER } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';
import { render, screen, waitForElementToBeRemoved, server } from 'test-utils';

const defaultCartItems = {
  code: 'mock-product',
  quantity: 3,
  plan: 'lifetime' as ProductPlans,
};

const renderApp = ({ cartItems = defaultCartItems }) => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <OrderItem orderItem={cartItems} isListItem />
    </PosDeviceStoreProvider>,
  );
};

describe('<OrderItem/>', () => {
  beforeEach(() => {
    server.use(getProductPricingHandler());
  });
  test('should render Order Item on screen', async () => {
    renderApp({});
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Mock Product')).toBeVisible();
    expect(screen.getByText('Qty: 3')).toBeVisible();
    expect(screen.getByText('Lifetime Plan')).toBeVisible();
  });

  test('should not render Order Item on screen if product description not available', async () => {
    const cartItems = {
      code: 'mock-product-new',
      quantity: 3,
      plan: 'lifetime' as ProductPlans,
    };
    renderApp({ cartItems });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.queryByText('Mock Product')).not.toBeInTheDocument();
  });
});
