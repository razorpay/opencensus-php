import React from 'react';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { render, screen } from 'test-utils';
import PrepayInsights from 'merchant/views/MagicCheckout/RTOAnalytics/common/PrepayInsights';
import {
  PREPAY_INSIGHTS_DATA,
  PREPAY_INSIGHTS_EMPTY_DATA,
} from 'merchant/views/MagicCheckout/RTOAnalytics/__tests__/mocks/fixtures';

const INIT_STATE = {
  magicRTOAnalytics: {
    startTime: 1691625600,
    endTime: 1694304000,
    prepay_order: {
      data: [
        {
          prepay_percentage: 10,
          total_discount: 100,
          total_order_amount: 1700,
        },
      ],
    },
  },
};

const renderApp = ({ state = {} as Record<string, any>, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <PrepayInsights {...props} />
    </Provider>,
  );
};

describe('testing prepay insight component', () => {
  test.each([PREPAY_INSIGHTS_DATA])('should render properly', (item) => {
    renderApp();
    expect(screen.getByText(new RegExp(item.label, 'i'))).toBeInTheDocument();
    expect(screen.getByText(item.value)).toBeInTheDocument();
  });

  test.each([PREPAY_INSIGHTS_EMPTY_DATA])('should render properly', (item) => {
    const customState = {
      magicRTOAnalytics: {
        startTime: 1691625600,
        endTime: 1694304000,
        prepay_order: {
          data: null,
        },
      },
    };

    renderApp({ state: customState });
    expect(screen.getByText(new RegExp(item.label, 'i'))).toBeInTheDocument();
    expect(screen.getAllByText(item.value).length).toBe(3);
  });
});
