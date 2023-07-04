import React from 'react';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { render, screen } from 'test-utils';
import Header from 'merchant/views/MagicCheckout/RTOAnalytics/containers/Header';

jest.mock('common/ui/DateRangePicker', () => () => <div>DateRangePicker</div>);
jest.mock('merchant/views/MagicCheckout/RTOAnalytics/common/CumulativeOrders', () => () => (
  <div>cumulative orders header</div>
));
jest.mock(
  'merchant/views/MagicCheckout/RTOAnalytics/common/RiskLevelOrderSplitCumulative',
  () => () => <div>risk level cumulative orders header</div>,
);

const renderApp = ({ state = {}, ...props }) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <Header {...props} />
    </Provider>,
  );
};

describe('testing header component', () => {
  test('should show manual review cumulative header if manual review is opted', () => {
    renderApp({ isManualReviewOpted: true });

    expect(screen.getByText(/^risk level cumulative orders header?/i)).toBeInTheDocument();
  });

  test('should show cumulative header if manual review is not opted', () => {
    renderApp({ isManualReviewOpted: false });

    expect(screen.getByText(/^cumulative orders header?/i)).toBeInTheDocument();
  });
});
