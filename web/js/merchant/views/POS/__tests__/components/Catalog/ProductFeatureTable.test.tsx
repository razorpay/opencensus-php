import React from 'react';
import { screen, render, waitFor, userEvent, waitForElementToBeRemoved, server } from 'test-utils';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import ProductFeatureTable from 'merchant/views/POS/Catalog/ProductFeatureTable';
import { ScrollObserverProvider } from 'merchant/views/POS/utils/ScrollObserver';
import { setupIntersectionObserverMock } from 'merchant/views/POS/utils/IntersectionObserverMock';
import { getProductPricingHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';

jest.spyOn(analytics, 'track_EXPERIMENTAL');

describe('ProductFeatureTable', () => {
  beforeEach(() => {
    setupIntersectionObserverMock();
    server.use(getProductPricingHandler());
  });
  test('should render ProductFeatureTableItem components', async () => {
    render(
      <ScrollObserverProvider>
        <PosDeviceStoreProvider user={MOCK_USER}>
          <ProductFeatureTable />
        </PosDeviceStoreProvider>
      </ScrollObserverProvider>,
    );
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    expect(screen.getByText('Choose the best devices for your business')).toBeVisible();
  });
  test('should call track_EXPERIMENTAL with the correct parameters on expand/contract of the table', async () => {
    render(
      <ScrollObserverProvider>
        <ProductFeatureTable
          isElevated={false}
          instrumentation={{
            section: 'Device Comparison',
            subSection: 'Device Comparison',
            l1FunnelStage: 'Device Exploration',
            l2FunnelStage: 'POS Catalog',
          }}
        />
      </ScrollObserverProvider>,
    );

    const link = screen.getByText('Show More');
    await userEvent.click(link);

    await waitFor(() => {
      expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.linkClicked, {
        label: 'Show More',
        whatsAppUpdates: 'No',
        section: 'Device Comparison',
        subSection: 'Device Comparison',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Catalog',
      });
    });

    expect(screen.getByText('Show Less')).toBeInTheDocument();
    await userEvent.click(link);

    await waitFor(() => {
      expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.linkClicked, {
        label: 'Show Less',
        whatsAppUpdates: 'No',
        section: 'Device Comparison',
        subSection: 'Device Comparison',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Catalog',
      });
    });
  });
});
