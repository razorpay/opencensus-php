import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import InstantRefundPricingTable from 'merchant/views/Transactions/v1/Payments/components/InstantRefundPricingTable';
import { fireEvent, render, screen, delay } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

describe('InstantRefundPricingTable', () => {
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
          <InstantRefundPricingTable {...defaultProps} {...rest} />
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

  test('should render InstantRefundPricingTable', () => {
    render(<App />);
    expect(screen.getByText('We charge minimal fee on each refund')).toBeInTheDocument();
  });

  describe.skip('Custom pricing', () => {
    test('should raiseTicket when contact support is clicked & rzpTicketSystem is true', async () => {
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
