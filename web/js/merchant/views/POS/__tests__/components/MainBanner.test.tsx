import React from 'react';

import MainBanner from 'merchant/views/POS/Catalog/MainBanner/MainBanner';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import * as posHooks from 'merchant/views/POS/hooks';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { screen, render, waitForElementToBeRemoved, userEvent, server } from 'test-utils';
import { ScrollObserverProvider } from 'merchant/views/POS/utils/ScrollObserver';
import { setupIntersectionObserverMock } from 'merchant/views/POS/utils/IntersectionObserverMock';

const mockedUsedNavigate = jest.fn();

const MOCK_PRODUCT_PRICING = [
  {
    name: 'Android Smart POS',
    code: 'a50',
    rate_config: {
      monthly: 300,
      lifetime: 12000,
      setup_fee: 200,
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

describe('<MainBanner/>', () => {
  afterEach(() => {
    jest.resetAllMocks();
  });

  beforeEach(() => {
    setupIntersectionObserverMock();
    server.use(getProductPricingHandler(MOCK_PRODUCT_PRICING));
  });

  test('should render main banner container', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Android Smart POS')).toBeVisible();
    expect(screen.getByText('All-in-one POS to support all your payment needs')).toBeVisible();
  });

  test('should render main banner CTAs', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Add to cart')).toBeVisible();
    expect(screen.getByText('Learn More')).toBeVisible();
  });

  test('should render features in main banner correctly', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    const features = screen.getAllByTestId('main-banner-features');
    expect(features[0]).toHaveTextContent('Accept card and UPI payments');
    expect(features[1]).toHaveTextContent('Uninterrupted connectivity over wifi / sim');
    expect(features[2]).toHaveTextContent('Instant audio confirmations');
    expect(features[3]).toHaveTextContent('In-built printer for printing charges slips');
  });

  test('should render main banner tiles', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    const mainBannerTilesContainer = screen.getByTestId('main-banner-tiles');
    expect(mainBannerTilesContainer.childNodes).toHaveLength(3);
    const tiles = mainBannerTilesContainer.childNodes;
    expect(tiles[0]).toHaveTextContent('Easy Card Swipe');
    expect(tiles[1]).toHaveTextContent('Quick Tap & Pay');
    expect(tiles[2]).toHaveTextContent('Efficient Billing Printer');
  });

  test('should render all prices on screen', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByTestId('monthly-amount')).toHaveTextContent(/300/);
    expect(screen.getByTestId('setup-amount')).toHaveTextContent(/200/);
    expect(screen.getByTestId('price-tag-amount')).toHaveTextContent(/300/);
  });

  test('should navigate to pdp if clicked on learn more', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByText('Learn More'));
    expect(mockedUsedNavigate).toHaveBeenCalledWith('/pos/catalog/a50');
  });

  test('should navigate to pdp if clicked on main banner and isMobile', async () => {
    jest.spyOn(posHooks, 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 's',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getByTestId('main-banner-wrapper'));
    expect(mockedUsedNavigate).toHaveBeenCalledWith('/pos/catalog/a50');
  });
});
