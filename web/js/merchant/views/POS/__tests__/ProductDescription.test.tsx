import React from 'react';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { setupIntersectionObserverMock } from 'merchant/views/POS/utils/IntersectionObserverMock';
import ProductDescription from 'merchant/views/POS/ProductDescription';
import { MOCK_USER, MOCK_GTM } from 'merchant/views/POS/__tests__/mocks/fixtures';
import {
  getProductPricingHandler,
  getPartnerProductPricingHandler,
} from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosStoreInitialState } from 'merchant/views/POS/constants';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { ScrollObserverProvider } from 'merchant/views/POS/utils/ScrollObserver';
import { render, screen, server, userEvent, waitFor, waitForElementToBeRemoved } from 'test-utils';

jest.spyOn(analytics, 'track_EXPERIMENTAL');

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  disconnect: jest.fn(),
}));

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useParams: () => ({
    productName: 'mock-product',
  }),
}));

jest.mock('common/splitz', () => ({
  ...(jest.requireActual('common/splitz') as Record<string, string>),
  useSplitzService: () => ({
    abExperiments: MOCK_GTM,
  }),
}));

window.scrollTo = jest.fn();

const renderApp = (initialState = PosStoreInitialState) => {
  render(
    <ScrollObserverProvider>
      <PosDeviceStoreProvider init={initialState} user={MOCK_USER}>
        <ProductDescription />
      </PosDeviceStoreProvider>
    </ScrollObserverProvider>,
  );
};

describe('<ProductDescription/>', () => {
  beforeEach(async () => {
    setupIntersectionObserverMock();
    server.use(getProductPricingHandler());
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
  });

  test('should render product descirption name and subtext on screen', () => {
    expect(screen.getByText(/Mock Product/)).toBeVisible();
    expect(screen.getByText(/Mock Description/)).toBeVisible();
  });
  test('should render product gallery container on screen', () => {
    const thumbnailImages = screen.getAllByAltText('product thumbnail image');
    expect(thumbnailImages.length).toBe(4);
    expect(screen.getByAltText('Product image-0')).toBeVisible();
  });
  test('should render product pricing cards on screen', () => {
    expect(screen.getByText(/Monthly Subscription/)).toBeVisible();
    expect(screen.getByText(/Lifetime Plan/)).toBeVisible();
  });
  test('should render product features gallery on screen', () => {
    expect(screen.getByAltText(/Test Feature Title/)).toBeVisible();
    expect(screen.getByText(/Test Feature Title/)).toBeVisible();
    expect(screen.getByText(/Test Feature Description/)).toBeVisible();
  });
  test('should render product info banner on screen', () => {
    expect(screen.getByAltText('pdp info banner')).toBeVisible();
    expect(screen.getByText(/Info Banner Feature 1/)).toBeVisible();
    expect(screen.getByText(/Info Banner Feature 2/)).toBeVisible();
  });
  test('should render product technical specs on screen', async () => {
    expect(screen.getByText('Technical Specifications')).toBeVisible();
    expect(screen.getByText('PayDroid powered by Android 6.0')).toBeVisible();
    await userEvent.click(screen.getByTestId('technical-spec-show-more-btn'));
    expect(screen.getByText('Test Technical Spec')).toBeVisible();
  });
  test('should call track_EXPERIMENTAL on page mount', () => {
    expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.pageViewed, {
      pageType: 'POS PDP',
      orderId: '',
    });
  });
  test('should call track_EXPERIMENTAL on click of View Pricing link', async () => {
    window.HTMLElement.prototype.scrollIntoView = jest.fn();
    const viewPricingLink = screen.getByText('View Pricing & TnC');
    await userEvent.click(viewPricingLink);
    await waitFor(() => {
      expect(analytics.track_EXPERIMENTAL).toHaveBeenLastCalledWith(SignUpEvents.linkClicked, {
        label: 'View Pricing & TnC',
        whatsAppUpdates: 'No',
        section: 'Device',
        subSection: 'Mock Product',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Product Description',
      });
    });
  });
});

describe('<ProductDescription/> with partner pricing', () => {
  beforeEach(() => {
    setupIntersectionObserverMock();
    server.use(getPartnerProductPricingHandler());
  });
  test('should render partner exclusive image', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByAltText('Pos Catalog Partner Exclusive')).toBeVisible();
  });
});
