import React from 'react';

import MainBanner from 'merchant/views/POS/Catalog/MainBanner/MainBanner';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import {
  getProductPricingHandler,
  getPartnerProductPricingHandler,
} from 'merchant/views/POS/__tests__/mocks/handlers';
import { ANDROID_SMART_POS, PRODUCT_OFFER_CONFIG } from 'merchant/views/POS/constants';
import * as posHelpers from 'merchant/views/POS/helpers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { setupIntersectionObserverMock } from 'merchant/views/POS/utils/IntersectionObserverMock';
import { ScrollObserverProvider } from 'merchant/views/POS/utils/ScrollObserver';
import { screen, render, waitForElementToBeRemoved, server } from 'test-utils';

const mockedUsedNavigate = jest.fn();

const MOCK_PRODUCT_PRICING = [
  {
    name: 'Android Smart POS',
    code: ANDROID_SMART_POS.code,
    rate_config: {
      monthly: 199,
      lifetime: 12000,
      setup_fee: 399,
    },
  },
];

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUsedNavigate,
}));

jest.unmock('merchant/views/POS/constants');

const renderApp = () => {
  render(
    <ScrollObserverProvider>
      <PosDeviceStoreProvider user={MOCK_USER}>
        <MainBanner />
      </PosDeviceStoreProvider>
    </ScrollObserverProvider>,
  );
};

describe('<MainBanner/> with offer', () => {
  afterEach(() => {
    jest.resetAllMocks();
  });

  beforeEach(() => {
    const fetchOffersSpy = jest.spyOn(posHelpers, 'fetchProductOffers');
    fetchOffersSpy.mockReturnValue({
      isEnabled: true,
      offers: PRODUCT_OFFER_CONFIG,
    });
    setupIntersectionObserverMock();
  });

  test('should render offer strip with text', async () => {
    server.use(getProductPricingHandler(MOCK_PRODUCT_PRICING));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Limited Time Offer till 31st May')).toBeVisible();
    expect(screen.getByTestId('prev-monthly')).toHaveTextContent('₹549');
    expect(screen.getByTestId('prev-setup')).toHaveTextContent('3,000');
    expect(screen.getByText('/month after 3 months*')).toBeVisible();
    expect(screen.getByText('setup fee')).toBeVisible();
    expect(screen.getByTestId('monthly-amount')).toHaveTextContent('299');
    expect(screen.getByTestId('setup-amount')).toHaveTextContent('399');
  });

  test('should render price tag with previous amount', async () => {
    server.use(getProductPricingHandler(MOCK_PRODUCT_PRICING));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByTestId('price-tag-prev-amount')).toHaveTextContent('549');
    expect(screen.getByTestId('price-tag-amount')).toHaveTextContent('299');
  });

  test('should render partner offer strip with text when entity is partner', async () => {
    const PARTNER_MOCK_PRODUCT_PRICING = [
      {
        ...MOCK_PRODUCT_PRICING[0],
        entity_type: 'partner',
      },
    ];
    server.use(getPartnerProductPricingHandler(PARTNER_MOCK_PRODUCT_PRICING));
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Partner Exclusive Time Offer till 31st May')).toBeVisible();
  });
});
