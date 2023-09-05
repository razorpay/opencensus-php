import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import InstantRefundFee from 'merchant/views/Transactions/v1/Payments/components/InstantRefundFee';
import { fireEvent, render, screen, delay } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

describe('InstantRefundFee', () => {
  const defaultProps = {
    pricing: {
      rules: [
        {
          fixed_rate: 100,
          amount_range_min: 100,
          amount_range_max: 10000,
        },
        {
          fixed_rate: 50,
          amount_range_min: 10,
          amount_range_max: 1000,
        },
      ],
    },
  };

  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <>
          <p>
            Request Description: <input name="request-description" type="text" defaultValue="" />
          </p>
          <InstantRefundFee {...defaultProps} {...rest} />
        </>
      </Provider>
    );
  };

  beforeAll(() => {
    window.rzpTicketSystem = true;
    jest.useFakeTimers();
    jest.spyOn(window, 'setTimeout');
  });

  afterAll(() => {
    window.rzpTicketSystem = false;
  });

  test('should render Fee for instant refund', () => {
    render(<App />);
    expect(screen.getByText('Fee for instant refund')).toBeInTheDocument();
  });

  test('should call rzpAnalytics when component mounts', () => {
    render(<App />);
    expect(window.rzpAnalytics).toHaveBeenCalledWith({
      eventAction: 'Minimal Fee',
      eventCategory: 'Dashboard - Instant Refund',
      eventLabel: 'Normal Pricing | Minimal Fee',
    });
  });

  describe('Custom pricing', () => {
    test('should call rzpAnalytics when component mounts & custom_pricing is true', () => {
      render(
        <App
          pricing={{
            custom_pricing: true,
          }}
        />,
      );
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventAction: 'Minimal Fee',
        eventCategory: 'Dashboard - Instant Refund',
        eventLabel: 'Custom Pricing | Minimal Fee',
      });
    });

    test.skip('should raiseTicket when contact support is clicked & rzpTicketSystem is true', async () => {
      render(
        <App
          pricing={{
            custom_pricing: true,
          }}
        />,
      );
      fireEvent.click(screen.getByText('contact support'));
      expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
      // wait for the setTimeout callback to be called
      await delay();
      expect(window.setTimeout).toHaveBeenCalledTimes(1);
      expect(window.setTimeout).toHaveBeenLastCalledWith(expect.any(Function), 0);
    });

    test('should not raiseTicket when contact support is clicked & rzpTicketSystem is false', () => {
      window.rzpTicketSystem = false;
      render(
        <App
          pricing={{
            custom_pricing: true,
          }}
        />,
      );
      fireEvent.click(screen.getByText('contact support'));
      expect(CreateTicketEmitter.emit).not.toHaveBeenCalledWith('create-ticket', 'tickets');
    });
  });
});
