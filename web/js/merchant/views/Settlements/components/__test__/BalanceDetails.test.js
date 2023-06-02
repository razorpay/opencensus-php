import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BalanceDetails from 'merchant/views/Settlements/components/BalanceDetails';
import { fireEvent, render, screen } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

const state = {
  session: {
    user: {
      merchant: {
        currency: 'INR',
      },
    },
    mode: 'test',
    org: {},
  },
  home: {
    current_balance: { data: { balance: -100 } },
    settlement_amount: { data: {} },
  },
  payments: {
    items: [],
  },
  settlement: {
    config: { data: {} },
  },
};

describe('BalanceDetails', () => {
  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <BalanceDetails {...rest} />
      </Provider>
    );
  };

  test('should render balance details', () => {
    render(<App />);
    const balanceDetailText = screen.getAllByText('Current Balance:');
    expect(balanceDetailText[0]).toBeInTheDocument();
  });

  describe('Balance Details', () => {
    test('should render negative balance', () => {
      const initialState = {
        ...state,
        home: {
          ...state.home,
          current_balance: { data: { balance: -100 } },
        },
      };
      render(<App initialState={initialState} />);
      const otherMethods = screen.getByLabelText('amount');
      expect(otherMethods).toHaveClass('amount-current-balance negative-balance');
    });
    test('should render positive balance', () => {
      const initialState = {
        ...state,
        home: {
          ...state.home,
          current_balance: { data: { balance: 100 } },
        },
      };
      render(<App initialState={initialState} />);
      const otherMethods = screen.getByLabelText('amount');
      expect(otherMethods).toHaveClass('amount-current-balance');
      expect(otherMethods).not.toHaveClass('amount-current-balance negative-balance');
    });
  });

  describe('Settlements', () => {
    test('should render settlement time', () => {
      const initialState = {
        ...state,
        home: {
          ...state.home,
          settlement_amount: {
            data: {
              next_settlement_time: true,
            },
          },
        },
      };
      render(<App initialState={initialState} />);
      const settlementText = screen.getByText('will be settled on');
      expect(settlementText).toBeInTheDocument();
    });
    test('should render next settlement time with delay reason', () => {
      const delayReason = 'Due to low bank balance';
      const initialState = {
        ...state,
        home: {
          ...state.home,
          settlement_amount: {
            data: {
              next_settlement_time: true,
              reason_for_delay: delayReason,
            },
          },
        },
      };
      render(<App initialState={initialState} />);
      const delayReasonText = screen.getByText(delayReason);
      expect(delayReasonText).toBeInTheDocument();
    });
    test('should render no settlement caption and reason', () => {
      const initialState = {
        ...state,
        session: { ...state.session, mode: 'live' },
        home: {
          ...state.home,
          settlement_amount: {
            data: {
              no_settlement: {
                on_hold: false,
                caption: 'No Settlement Available',
                reason: 'Lost ATM Card',
              },
            },
          },
        },
        payments: {
          items: [1, 2],
        },
      };
      render(<App initialState={initialState} />);
      const noSettlementCaption = screen.getByText('No Settlement Available');
      expect(noSettlementCaption).toBeInTheDocument();
      const noSettlementReason = screen.getByText('Lost ATM Card');
      expect(noSettlementReason).toBeInTheDocument();
    });
  });

  describe('Know More Action', () => {
    test('should call analytics on know more', () => {
      const initialState = {
        ...state,
        home: {
          ...state.home,
          settlement_amount: {
            data: {
              next_settlement_time: true,
            },
          },
        },
      };
      render(<App initialState={initialState} />);
      const knowMoreBtn = screen.getByText('Know More');
      fireEvent.click(knowMoreBtn);
      expect(window.rzpAnalytics).toHaveBeenCalledWith({
        eventCategory: 'Settlement Revamp',
        eventAction: 'Know more - Next Settlement',
        eventLabel: `Settlements`,
      });
    });
  });
});
