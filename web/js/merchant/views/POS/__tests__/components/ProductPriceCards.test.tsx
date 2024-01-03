import React from 'react';

import ProductPriceCards from 'merchant/views/POS/ProductDescription/ProductPriceCards';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PRODUCT_PLANS, PosStoreInitialState } from 'merchant/views/POS/constants';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { render, screen, server, userEvent, waitForElementToBeRemoved } from 'test-utils';

const initProps = {
  selectedPricing: PRODUCT_PLANS.MONTHLY,
  productCode: 'mock-product',
  onPricingPlanChange: jest.fn(),
};

const renderApp = (initialState = PosStoreInitialState) => {
  render(
    <PosDeviceStoreProvider init={initialState} user={MOCK_USER}>
      <ProductPriceCards {...initProps} />
    </PosDeviceStoreProvider>,
  );
};

describe('<ProductPriceCards/>', () => {
  beforeEach(async () => {
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });
  test('should render Product price cards on screen', () => {
    expect(screen.getByText(/Monthly Subscription/)).toBeVisible();
    expect(screen.getByText(/Lifetime Plan/)).toBeVisible();

    expect(
      screen.getByText(
        '*Subscription only starts when device gets delivered. GST charges applicable.',
      ),
    ).toBeVisible();

    expect(screen.getByText('*No Setup fees required. GST charges applicable.')).toBeVisible();
  });

  test('should render prices on screen', () => {
    expect(screen.getByText('300')).toBeVisible();
    expect(screen.getByText('200')).toBeVisible();
    expect(screen.getByText('12,000')).toBeVisible();
  });

  test('should highlight Product price cards on click', async () => {
    await userEvent.click(screen.getByTestId('monthly-price-card'));
    expect(screen.getByTestId('monthly-price-card').getAttribute('aria-selected')).toBe('true');
    expect(initProps.onPricingPlanChange).toHaveBeenCalledWith(PRODUCT_PLANS.MONTHLY);
  });
});
