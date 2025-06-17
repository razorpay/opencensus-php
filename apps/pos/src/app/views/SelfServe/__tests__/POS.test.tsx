import React from 'react';

import Pos from 'apps/pos/src/app/views/SelfServe';
import { getProductPricingHandler } from 'apps/pos/src/app/views/SelfServe/__tests__/mocks/handlers';
import { render, waitFor, screen, server, userEvent } from 'test-utils';

import { MOCK_PRICING_PLAN } from './mocks/fixtures';
import { setupIntersectionObserverMock } from 'apps/pos/src/app/views/SelfServe/utils/IntersectionObserverMock';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

jest.spyOn(analytics, 'track_EXPERIMENTAL');

jest.mock('apps/pos/src/app/views/SelfServe/services', () => ({
  ...(jest.requireActual('apps/pos/src/app/views/SelfServe/services') as Record<string, string>),
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
        whatsAppUpdates: 'No',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Catalog',
        label: 'Orders',
        section: 'POS Catalog',
        subSection: 'POS Catalog',
      });
    });

    const deviceShopTab = screen.getByText('Device Shop');
    await userEvent.click(deviceShopTab);
    await waitFor(() => {
      expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.linkClicked, {
        label: 'Device Shop',
        whatsAppUpdates: 'No',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Catalog',
        section: 'POS Catalog',
        subSection: 'POS Catalog',
      });
    });
  });
});
