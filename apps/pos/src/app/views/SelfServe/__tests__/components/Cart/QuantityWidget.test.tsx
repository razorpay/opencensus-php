import React from 'react';

import QuantityWidget from 'apps/pos/src/app/views/SelfServe/Cart/QuantityWidget';
import { MOCK_USER } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';
import { render, screen, userEvent, waitForElementToBeRemoved, server } from 'test-utils';

const initProps = {
  cartItem: {
    code: 'mock-product',
    quantity: 0,
    plan: 'lifetime' as ProductPlans,
  },
  onProductQuantityUpdate: jest.fn(),
  productTitle: '',
};

const renderApp = () => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <QuantityWidget {...initProps} />
    </PosDeviceStoreProvider>,
  );
};

describe('<QuantityWidget/>', () => {
  beforeEach(async () => {
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });

  test('should render quantiy widget on screen', () => {
    expect(screen.getByLabelText('reduce cart quantity')).toBeVisible();
    expect(screen.getByLabelText('increase cart quantity')).toBeVisible();
    expect(screen.getByText('0')).toBeVisible();
  });

  test('should render decreased quantity when clicked on quantity decrease', async () => {
    expect(screen.getByLabelText('reduce cart quantity')).toBeDisabled();
    await userEvent.click(screen.getByLabelText('increase cart quantity'));
    await userEvent.click(screen.getByLabelText('reduce cart quantity'));
    expect(screen.getByText('0')).toBeVisible();
  });

  test('should increase quantiy if clicked on increase quantity', async () => {
    const product = {
      productCode: 'mock-product',
      plan: 'lifetime',
    };
    await userEvent.click(screen.getByLabelText('increase cart quantity'));
    expect(initProps.onProductQuantityUpdate).toHaveBeenCalledWith(
      'add',
      product,
      initProps.productTitle,
    );
  });
});
