import React from 'react';

import MobileCardContainer from 'merchant/views/POS/Catalog/ProductCards/MobileCardContainer';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
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

describe('<MainBanner/>', () => {
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
