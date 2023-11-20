import store from 'merchant/store';
import SettlementBreakUp from 'merchant/views/Settlements/v3/components/Breakup/BreakupRevamp';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';

const globalStore = store.getState();

const getInitialState = ({ breakupDetails = {} }) => {
  return {
    ...globalStore,
    session: {
      ...globalStore.session,
      user: {
        merchant: {
          currency: 'INR',
        },
      },
    },
    settlement: {
      ...globalStore.settlement,
      breakupDetails: {
        ...globalStore.settlement.breakupDetails,
        ...breakupDetails,
      },
    },
  };
};

const renderApp = ({ initialState }) =>
  render(<SettlementBreakUp />, {
    initialState,
  });

describe('SettlementBreakUp revamp', () => {
  test('should render shimmer incase of api loading', () => {
    const initialState = getInitialState({ breakupDetails: { loading: true } });
    renderApp({ initialState });
    expect(screen.getByTestId('breakup-shimmer')).toBeInTheDocument();
  });

  test('should not render breakup if breakupDetails return error', () => {
    const initialState = getInitialState({
      breakupDetails: {
        loading: false,
        error: { status: false },
      },
    });
    renderApp({ initialState });
    expect(screen.queryByText('Breakup')).not.toBeInTheDocument();
  });

  test('should render gross settlement, deductions and net settlementafter api success', () => {
    const initialState = getInitialState({
      breakupDetails: {
        loading: false,
        items: [
          {
            component: 'reversal',
            amount: 200,
            count: 1,
            type: 'credit',
            fee: 50,
            tax: 50,
          },
        ],
        isBreakupNew: true,
      },
    });
    renderApp({ initialState });
    expect(screen.getByText('Gross settlement')).toBeInTheDocument();
    expect(screen.getByText('Deductions')).toBeInTheDocument();
    expect(screen.getByText('Net settlements')).toBeInTheDocument();
  });

  test('should hide the breakup entries when toggler clicked', async () => {
    const initialState = getInitialState({
      breakupDetails: {
        loading: false,
        items: [
          {
            component: 'reversal',
            amount: 200,
            count: 1,
            type: 'credit',
            fee: 50,
            tax: 50,
          },
          {
            component: 'refund_inter',
            amount: 100,
            count: 1,
            type: 'debit',
            fee: 50,
            tax: 50,
          },
        ],
        isBreakupNew: true,
      },
    });
    renderApp({ initialState });
    const toggler = screen.getByText('Gross settlement');
    await userEvent.click(toggler);
    expect(screen.getByText('Reversals')).toBeInTheDocument();
  });
});
