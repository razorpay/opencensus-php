import React from 'react';
import Header from 'merchant/views/MagicCheckout/OrderAnalytics/common/Header';
import '@testing-library/jest-dom/extend-expect';
import 'react-dates/initialize';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import moment from 'moment';
import { OrderAnalyticsProvider } from 'merchant/views/MagicCheckout/OrderAnalytics/OrderAnalyticsContext';

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <OrderAnalyticsProvider>
        <Header {...props} />
      </OrderAnalyticsProvider>
    </Provider>
  );
};

describe('Magic - Header', () => {
  test('should render the header for Magic Checkout', () => {
    const setTimeRange = jest.fn();
    render(
      <App
        updated_at={moment.unix()}
        setTimeRange={setTimeRange}
        dashboardView={'magic_checkout'}
      />,
    );
    expect(
      screen.getByText('This data is only for Razorpay Magic processed orders'),
    ).toBeInTheDocument();
  });
  test('should render the header for MagicX', () => {
    const setTimeRange = jest.fn();
    render(<App updated_at={moment.unix()} setTimeRange={setTimeRange} dashboardView={'rcod'} />);
    expect(
      screen.getByText('This data is only for Razorpay MagicX processed orders'),
    ).toBeInTheDocument();
  });
  test('should call setTimeRange on date change', async () => {
    const setTimeRange = jest.fn();
    render(<App updated_at={moment.unix()} setTimeRange={setTimeRange} />);
    expect(
      screen.getByText('This data is only for Razorpay Magic processed orders'),
    ).toBeInTheDocument();
    const dropBtn = screen.getByText('Past 2 days');
    expect(dropBtn).toBeInTheDocument();
    await userEvent.click(dropBtn);
    const rangeBtn = screen.queryByText('Past 7 days');
    expect(rangeBtn).toBeInTheDocument();
    await userEvent.click(rangeBtn);
    expect(setTimeRange).toBeCalledTimes(2); // gets called twice since end date also changes
  });
});
