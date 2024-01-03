import React from 'react';

import MobilePos from 'merchant/views/POS/Catalog/ProductCards/MobilePos';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { render, screen, waitForElementToBeRemoved, server } from 'test-utils';

jest.unmock('merchant/views/POS/constants');

const MOCK_PRODUCT_PRICING = [
  {
    name: 'Mobile POS',
    code: 'd180',
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
];

const renderApp = () => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <MobilePos />
    </PosDeviceStoreProvider>,
  );
};

describe('<MobilePos/>', () => {
  beforeEach(async () => {
    server.use(getProductPricingHandler(MOCK_PRODUCT_PRICING));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });
  test('should render Mobile POS card with content and CTAs', () => {
    expect(screen.getByText('Mobile POS (mPOS)')).toBeVisible();
    expect(screen.getByText('Pocket-sized and affordable')).toBeVisible();

    expect(screen.getByTestId('pricing-details-text')).toHaveTextContent(
      '₹300 /month + ₹200 setup fee',
    );
    expect(screen.getByText('*Lifetime Pricing also available.')).toBeVisible();

    expect(screen.getByText('Add to cart')).toBeVisible();
    expect(screen.getByText('Learn More')).toBeVisible();

    expect(screen.getByAltText('mobile pos image')).toBeVisible();
  });
});
