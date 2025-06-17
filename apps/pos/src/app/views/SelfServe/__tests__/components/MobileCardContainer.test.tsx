import React from 'react';

import MobileCardContainer from 'apps/pos/src/app/views/SelfServe/Catalog/ProductCards/MobileCardContainer';
import {
  MOCK_USER,
  MOCK_PRODUCT_OFFER_CONFIG,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/fixtures';
import {
  getProductPricingHandler,
  getPartnerProductPricingHandler,
} from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import * as posHelpers from 'apps/pos/src/app/views/SelfServe/helpers';
import { PosDeviceStoreProvider } from 'apps/pos/src/app/views/SelfServe/providers';
import { screen, render, waitForElementToBeRemoved, server } from 'test-utils';

const renderApp = ({ productName }) => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <MobileCardContainer
        code={productName ?? 'mock-product'}
        image="mock-product-image"
        cardDescription="mock-product-description"
      />
    </PosDeviceStoreProvider>,
  );
};

describe('<MobileCardContainer/>', () => {
  beforeEach(() => {
    server.use(getProductPricingHandler());
  });
  test('should render all content on screen if product desc. exits ', async () => {
    renderApp({ productName: null });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Mock Product')).toBeVisible();
    expect(screen.getByText('mock-product-description')).toBeVisible();
  });

  test('should  not render content on screen if product desc. do not exits ', async () => {
    renderApp({ productName: 'random-product' });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.queryByText('Random Product')).not.toBeInTheDocument();
  });
});

describe('<MobileCardContainer/> - partner price', () => {
  beforeEach(() => {
    server.use(getPartnerProductPricingHandler());
  });
  test('should render partner exclusive image if partner price exists', async () => {
    renderApp({ productName: null });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByAltText('Partner Exclusive')).toBeVisible();
  });
});

describe('<MobileCardContainer/> with offer', () => {
  test('should render offer strip and pricing content on screen ', async () => {
    server.use(getProductPricingHandler());
    const fetchOffersSpy = jest.spyOn(posHelpers, 'fetchProductOffers');
    fetchOffersSpy.mockReturnValue({
      isEnabled: true,
      offers: MOCK_PRODUCT_OFFER_CONFIG,
    });
    renderApp({ productName: null });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Mock offer text')).toBeVisible();
    expect(screen.getByTestId('monthly-offer-amount-text')).toHaveTextContent(
      '₹299 ₹400 /month after 3 months*',
    );

    expect(screen.getByTestId('setup-offer-amount-text')).toHaveTextContent('₹200 ₹300 setup fee');
  });

  test('should render partner offer strip and pricing content on screen ', async () => {
    server.use(getPartnerProductPricingHandler());
    const fetchOffersSpy = jest.spyOn(posHelpers, 'fetchProductOffers');
    fetchOffersSpy.mockReturnValue({
      isEnabled: true,
      offers: MOCK_PRODUCT_OFFER_CONFIG,
    });
    renderApp({ productName: null });
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(
      screen.getByText(MOCK_PRODUCT_OFFER_CONFIG['mock-product'].partnerOfferText),
    ).toBeVisible();
  });
});
