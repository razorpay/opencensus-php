import React from 'react';

import Pos from 'merchant/views/POS';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { render, waitFor, screen, server, userEvent } from 'test-utils';

import { MOCK_PRICING_PLAN } from './mocks/fixtures';
import { setupIntersectionObserverMock } from 'merchant/views/POS/utils/IntersectionObserverMock';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

jest.mock('merchant/views/POS/services', () => ({
  ...(jest.requireActual('merchant/views/POS/services') as Record<string, string>),
  getPricingPlan: () => MOCK_PRICING_PLAN,
}));

const renderApp = ({ intialRoute = '/pos' }) => {
  return render(<Pos />, {
    renderOptions: {
      initialEntries: [intialRoute],
    },
  });
};

describe('<POS/>', () => {
  beforeEach(() => {
    setupIntersectionObserverMock();
  });
  test('should render product wrapper with catalog tab', async () => {
    server.use(getProductPricingHandler());
    renderApp({ intialRoute: '/pos/catalog' });
    await waitFor(() => {
      expect(screen.getByText('Device Shop')).toBeVisible();
    });
  });
  test('should call track_EXPERIMENTAL on click of Orders tab and Device Shop tab', async () => {
    server.use(getProductPricingHandler());
    renderApp({ intialRoute: '/pos/catalog' });
    await waitFor(() => {
      expect(screen.getByText('Device Shop')).toBeVisible();
      expect(screen.getByText('Orders')).toBeVisible();
    });
    const ordersTab = screen.getByText('Orders');
    await userEvent.click(ordersTab);
    await waitFor(() => {
      expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.linkClicked, {
        label: 'Orders',
        section: 'POS Product Description',
        subSection: 'POS Product Description',
        whatsAppUpdates: 'No',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Product Description',
      });
    });

    const deviceShopTab = screen.getByText('Device Shop');
    await userEvent.click(deviceShopTab);
    await waitFor(() => {
      expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.linkClicked, {
        label: 'Device Shop',
        section: 'POS Product Description',
        whatsAppUpdates: 'No',
        subSection: 'POS Product Description',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Product Description',
      });
    });
  });
});
