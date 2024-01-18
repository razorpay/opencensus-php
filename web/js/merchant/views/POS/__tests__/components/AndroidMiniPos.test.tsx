import React from 'react';

import AndroidMiniPos from 'merchant/views/POS/Catalog/ProductCards/AndroidMiniPos';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { render, screen, waitForElementToBeRemoved, server } from 'test-utils';
import { ANDROID_MINI_POS } from 'merchant/views/POS/constants';

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
