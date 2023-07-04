import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import ManualReviewOrderSplit from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/ManualReviewOrderSplit';
import { MANUAL_REVIEW_ORDER_SPLIT_DATA } from 'merchant/views/MagicCheckout/RTOAnalytics/__tests__/mocks/fixtures';

jest.mock('common/ui/BtnGroup/index', () => ({
  __esModule: true,
  ...(jest.requireActual('common/ui/BtnGroup/index') as Record<string, unknown>),
  BtnGroup: (props) => {
    return (
      <div data-testId="breakdown-btngroup" onClick={() => props.onChange('monthly')}>
        Btn group
      </div>
    );
  },
}));

const INIT_STATE = {
  magicRTOAnalytics: {
    manual_review_order_split: {
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
      <ManualReviewOrderSplit {...props} />
    </Provider>,
  );
};

describe('testing manual reveiw order split component', () => {
  test('should render properly', () => {
    renderApp();
    expect(screen.getByText(/^COD Order Review Report?/i)).toBeInTheDocument();
  });

  test('should show some graph', () => {
    const customState = {
      magicRTOAnalytics: {
        manual_review_order_split: {
          loading: false,
          data: MANUAL_REVIEW_ORDER_SPLIT_DATA,
          updatedAt: 1666302630,
        },
        startTime: null,
        endTime: null,
        timedWidgetsFetching: false,
      },
    };

    renderApp({ state: customState });
    expect(
      screen.queryByText(/^There is no data available for the selected date-range.?/i),
    ).not.toBeInTheDocument();
  });

  test('should be able to change breakdown value', async () => {
    const customState = {
      magicRTOAnalytics: {
        manual_review_order_split: {
          loading: false,
          data: MANUAL_REVIEW_ORDER_SPLIT_DATA,
          updatedAt: 1666302630,
        },
        startTime: 1684195200,
        endTime: 1684195200,
        timedWidgetsFetching: false,
      },
    };
    renderApp({ state: customState });

    const dayBreakdownCta = screen.getByTestId('breakdown-btngroup');

    userEvent.click(dayBreakdownCta);

    await waitFor(() => {
      expect(
        screen.queryByText(/^There is no data available for the selected date-range.?/i),
      ).not.toBeInTheDocument();
    });
  });
});
