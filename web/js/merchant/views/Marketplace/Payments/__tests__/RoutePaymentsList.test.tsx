import React from 'react';
import { render, waitFor } from 'test-utils';
import PaymentsList from 'merchant/views/Marketplace/Payments/List';
import * as analytics from 'common/utils/analytics';
import { state } from 'merchant/views/Marketplace/PlatformFee/__tests__/PlatformFeeList.test';
import 'react-dates/initialize';

const location = {
  search: '',
  pathname: '/route/payments',
};

describe('PaymentsList', () => {
  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  const renderApp = (isPlatformFeeTabEnabled = false) => {
    render(<PaymentsList isPlatformFeeTabEnabled={isPlatformFeeTabEnabled} location={location} />, {
      initialState: state,
      renderViaRouteGuard: false,
    });
  };

  test('should capture platformFee tab displayed event if required conditions are true', async () => {
    renderApp(true);
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        screen: 'route payment page',
        objectName: 'route partnership platform fee',
        actionName: 'tab displayed',
        properties: {
          mid: 'testUserId',
        },
        toLumberjack: true,
      });
    });
  });
});
