import React from 'react';
import { render, screen, waitFor, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import RTORate from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/RTORate';
import { rtoRateHandlers } from 'merchant/views/MagicCheckout/RTOAnalytics/__tests__/mocks/handler';

const INIT_STATE = {
  magicRTOAnalytics: {
    rto_rate: {
      loading: false,
      data: null,
      updatedAt: null,
    },
    startTime: null,
    endTime: null,
    timedWidgetsFetching: false,
  },
};

const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <RTORate {...props} />
    </Provider>,
  );
};

describe('testing rto rate component', () => {
  test('should render properly', async () => {
    server.use(rtoRateHandlers[0]);

    const customState = {
      ...INIT_STATE,
      magicRTOAnalytics: {
        ...INIT_STATE.magicRTOAnalytics,
        startTime: '1684195200',
        endTime: '1686813520',
      },
    };

    renderApp({ state: customState });
    await waitFor(() => {
      expect(screen.getByText(/^RTO rate with Magic Checkout?/i)).toBeInTheDocument();
    });
  });

  test('should show graph properly', async () => {
    server.use(rtoRateHandlers[1]);

    const customState = {
      ...INIT_STATE,
      magicRTOAnalytics: {
        ...INIT_STATE.magicRTOAnalytics,
        startTime: '1684195200',
        endTime: '1686813520',
      },
    };

    renderApp({ state: customState });
    await waitFor(() => {
      expect(
        screen.queryByText(/^There is no data available for the selected date-range.?/i),
      ).not.toBeInTheDocument();
    });
  });
});
