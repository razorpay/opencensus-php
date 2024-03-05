import React from 'react';

import OfferPriceCardContent from 'merchant/views/POS/ProductDescription/ProductPriceCards/OfferPriceCardContent';
import {
  MOCK_PRICING_WITH_PRICES,
  MOCK_USER,
  MOCK_PRODUCT_OFFER_CONFIG,
} from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import * as posHelpers from 'merchant/views/POS/helpers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { render, screen, waitForElementToBeRemoved, server } from 'test-utils';

const renderApp = () => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <OfferPriceCardContent pricing={MOCK_PRICING_WITH_PRICES[0]} />
    </PosDeviceStoreProvider>,
  );
};

describe('<OfferPriceCardContent/>', () => {
  test('should render offer price cards on screen with details and variants', async () => {
    server.use(getProductPricingHandler());

    const fetchOffersSpy = jest.spyOn(posHelpers, 'fetchProductOffers');
    fetchOffersSpy.mockReturnValue({
      isEnabled: true,
      offers: MOCK_PRODUCT_OFFER_CONFIG,
    });
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('1,200')).toBeVisible();
    expect(screen.getByText('rental after 3 months')).toBeVisible();
    expect(screen.getByText('setup fee')).toBeVisible();
    expect(screen.getByText('MDR upto 1L transaction')).toBeVisible();
  });
});
