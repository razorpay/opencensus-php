import React from 'react';

import DetailedPricingAndTncWrapper from 'apps/pos/src/app/views/SelfServe/ProductDescription/DetailedPricingAndTncWrapper';
import {
  MOCK_USER,
  MOCK_PRODUCT_OFFER_CONFIG,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import * as posHelpers from 'apps/pos/src/app/views/SelfServe/helpers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { render, screen, waitForElementToBeRemoved, server } from 'test-utils';

const renderApp = () => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <DetailedPricingAndTncWrapper productCode="mock-product" />
    </PosDeviceStoreProvider>,
  );
};

describe('<DetailedPricingAndTncWrapper/>', () => {
  test('should render detailed pricing and tnc on screen for non offer products', async () => {
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Detailed Pricing')).toBeInTheDocument();
    expect(screen.getByText('Terms & Conditions')).toBeInTheDocument();
    expect(screen.getByText('Monthly Plan Pricing')).toBeInTheDocument();
    expect(screen.getByText('Lifetime Pricing')).toBeInTheDocument();
  });

  test('should render detailed pricing and tnc with offers on screen for products with offers', async () => {
    const fetchOffersSpy = jest.spyOn(posHelpers, 'fetchProductOffers');
    fetchOffersSpy.mockReturnValue({
      isEnabled: true,
      offers: MOCK_PRODUCT_OFFER_CONFIG,
    });
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Detailed Pricing')).toBeInTheDocument();
    expect(screen.getByText('Detailed Pricing')).toBeInTheDocument();
    expect(screen.getByText(/charges upto ₹1L transactions/)).toBeInTheDocument();
    expect(screen.getByText('Offer Terms & Conditions')).toBeInTheDocument();
    expect(screen.getByText('Monthly Plan Pricing')).toBeInTheDocument();
    expect(screen.getByText('Lifetime Pricing')).toBeInTheDocument();
  });
});
