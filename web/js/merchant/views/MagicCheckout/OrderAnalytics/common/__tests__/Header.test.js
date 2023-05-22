import React from 'react';
import Header from 'merchant/views/MagicCheckout/OrderAnalytics/common/Header';
import '@testing-library/jest-dom/extend-expect';
import 'react-dates/initialize';
import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import moment from 'moment';

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <Header {...props} />
    </Provider>
  );
};

describe('Magic - Header', () => {
  test('should render the header', () => {
    const setTimeRange = jest.fn();
    render(<App updated_at={moment.unix()} setTimeRange={setTimeRange} />);
    expect(
      screen.getByText('This data is only for razorpay magic processed orders'),
    ).toBeInTheDocument();
  });
  test('should call setTimeRange on date change', async () => {
    const setTimeRange = jest.fn();
    render(<App updated_at={moment.unix()} setTimeRange={setTimeRange} />);
    expect(
      screen.getByText('This data is only for razorpay magic processed orders'),
    ).toBeInTheDocument();
    const dropBtn = screen.getByText('Past 48 hours');
    expect(dropBtn).toBeInTheDocument();
    await userEvent.click(dropBtn);
    const rangeBtn = screen.queryByText('Past 24 hours');
    expect(rangeBtn).toBeInTheDocument();
    await userEvent.click(rangeBtn);
    expect(setTimeRange).toBeCalledTimes(1);
  });
});
