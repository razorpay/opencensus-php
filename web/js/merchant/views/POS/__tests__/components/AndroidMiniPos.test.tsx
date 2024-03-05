import React from 'react';

import AndroidMiniPos from 'merchant/views/POS/Catalog/ProductCards/AndroidMiniPos';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { ANDROID_MINI_POS, PRODUCT_OFFER_CONFIG } from 'merchant/views/POS/constants';
import * as posHelpers from 'merchant/views/POS/helpers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { render, screen, waitForElementToBeRemoved, server } from 'test-utils';

jest.unmock('merchant/views/POS/constants');

const renderApp = () => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <AndroidMiniPos />
    </PosDeviceStoreProvider>,
  );
};

const MOCK_PRODUCT_PRICING = [
  {
    name: 'Android Mini POS',
    code: ANDROID_MINI_POS.code,
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
    },
  },
];

describe('<AndroidMiniPos/>', () => {
  beforeEach(async () => {
    server.use(getProductPricingHandler(MOCK_PRODUCT_PRICING));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });
  test('should render Android Smart Mini Card with content and CTAs', () => {
    expect(screen.getByText('Android Smart Mini POS')).toBeVisible();
    expect(screen.getByText('Feature packed and portable')).toBeVisible();

    expect(screen.getByTestId('pricing-details-text')).toHaveTextContent(
      '₹300 /month + ₹200 setup fee',
    );
    expect(screen.getByText('*Lifetime Pricing also available.')).toBeVisible();

    expect(screen.getByText('Add to cart')).toBeVisible();
    expect(screen.getByText('Learn More')).toBeVisible();

    expect(screen.getByAltText('android smart mini image')).toBeVisible();
  });
});

describe('<AndroidMiniPos/> with offer', () => {
  beforeEach(async () => {
    const fetchOffersSpy = jest.spyOn(posHelpers, 'fetchProductOffers');
    fetchOffersSpy.mockReturnValue({
      isEnabled: true,
      offers: PRODUCT_OFFER_CONFIG,
    });
    server.use(getProductPricingHandler(MOCK_PRODUCT_PRICING));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });

  test('should render offer strip with other content', () => {
    expect(screen.getByTestId('monthy-pricing-text')).toHaveTextContent(
      '₹249 ₹499 /month after 3 months*',
    );

    expect(screen.getByTestId('setup-pricing-text')).toHaveTextContent('₹200 ₹2,000 setup fee');
    expect(screen.getByText('Limited Time Offer till 31st March')).toBeVisible();
  });
});
