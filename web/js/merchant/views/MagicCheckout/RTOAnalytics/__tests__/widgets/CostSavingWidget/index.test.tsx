import React from 'react';
import { render, screen, waitFor, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import CostSaved from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/CostSaved';
import { costSavedWidgetHandlers } from 'merchant/views/MagicCheckout/RTOAnalytics/__tests__/mocks/handler';

const INIT_STATE = {
  magicRTOAnalytics: {
    cost_saving: {
      loading: false,
      data: null,
      updatedAt: null,
    },
    startTime: null,
    endTime: null,
    timedWidgetsFetching: false,
  },
  magicCheckout: {
    cod_order_control: true,
  },
};

const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <CostSaved {...props} />
    </Provider>,
  );
};

describe('testing cost saved component', () => {
  test('should show proper graph title if manual review opted', async () => {
    server.use(costSavedWidgetHandlers[0]);

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
        screen.getByText(/^Cost saved due to manual review of COD orders?/i),
      ).toBeInTheDocument();
    });
  });

  test('should show proper graph title if manual review is not opted', async () => {
    server.use(costSavedWidgetHandlers[1]);

    const customState = {
      ...INIT_STATE,
      magicRTOAnalytics: {
        ...INIT_STATE.magicRTOAnalytics,
        startTime: '1684195200',
        endTime: '1686813520',
      },
      magicCheckout: {
        cod_order_control: false,
      },
    };

    renderApp({ state: customState });
    expect(screen.getByText(/^Cost saved by COD Intelligence?/i)).toBeInTheDocument();

    await waitFor(() => {
      expect(
        screen.queryByText(/^There is no data available for the selected date-range.?/i),
      ).not.toBeInTheDocument();
    });
  });
});
