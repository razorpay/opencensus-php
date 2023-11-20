import React from 'react';
import { render, screen } from 'test-utils';
import store from 'merchant/store';
import SettlementDetailsOverview from 'merchant/views/Settlements/v3/components/SettlementDetailsOverview';
import { useMobile } from 'common/hooks/useMobile';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

const globalStore = store.getState();

const getInitialState = ({ settlement = {}, currency = undefined }: any) => {
  return {
    ...globalStore,
    session: {
      ...globalStore.session,
      user: {
        merchant: {
          currency,
        },
      },
    },
    settlement: {
      ...globalStore.settlement,
      settlement: {
        ...globalStore.settlement.settlement,
        amount: 23886,
        created_at: 1678077015,
        entity: 'settlement',
        fees: 0,
        id: 'setl_JCVHSjHRi9QHto',
        status: 'processed',
        tax: 0,
        utr: 'cg2mpl08cfbf3p7nghfg',
        ...settlement,
      },
    },
  };
};

const renderApp = ({ initialState }) =>
  render(<SettlementDetailsOverview />, {
    initialState,
  });
describe('SettlementDetailsOverview revamp', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  test('should render settlement status', () => {
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Processed')).toBeInTheDocument();
    expect(
      screen.getByText('Created on Mon Mar 6', {
        exact: false,
      }),
    ).toBeInTheDocument();
  });

  test('should render settlement time', () => {
    const initialState = getInitialState({ currency: 'INR' });
    renderApp({ initialState });
    expect(
      screen.getByText('Created on Mon Mar 6', {
        exact: false,
      }),
    ).toBeInTheDocument();
  });

  test('should render settlement status on mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Processed')).toBeInTheDocument();
    expect(
      screen.getByText('Created on Mon Mar 6', {
        exact: false,
      }),
    ).toBeInTheDocument();
  });
});
