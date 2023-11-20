import React from 'react';
import { checkIfComponentIsEmpty, render, screen, userEvent, waitFor } from 'test-utils';
import store from 'merchant/store';
import GrossSettlementsEntities from 'merchant/views/Settlements/v3/components/GrossSettlementsEntities/GrossSettlementsEntitiesRevamp';
import { useMobile } from 'common/hooks/useMobile';
import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';

const initBreakupDetails = SettlementsDB.settlementTabBreakupDetails;

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

const globalStore = store.getState();

const getInitialState = ({ settlement = {}, breakup = {} }) => {
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
      breakupDetails: { ...initBreakupDetails, ...breakup },
    },
  };
};

const renderApp = ({ initialState }) =>
  render(<GrossSettlementsEntities />, {
    initialState,
  });
describe('GrossSettlementsEntities revamp', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  test('should render gross entities', () => {
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Gross Settlements')).toBeInTheDocument();
    expect(screen.getByText('Payment')).toBeInTheDocument();
    expect(screen.getByText('Reversal')).toBeInTheDocument();
  });

  test('should render able to change entity tab', async () => {
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Payment')).toBeInTheDocument();
    expect(screen.queryByText('Payment ID')).not.toBeInTheDocument();
    userEvent.click(screen.getByText('Payment'));
    await waitFor(() => {
      expect(screen.getByText('Payment ID')).toBeInTheDocument();
    });
  });

  test('should render empty message if data not present', () => {
    const initialState = getInitialState({
      breakup: {
        items: [],
      },
    });
    renderApp({ initialState });
    expect(
      screen.getByText('No transactions were detected for this settlement'),
    ).toBeInTheDocument();
  });

  test('should render empty message if data not present', () => {
    const initialState = getInitialState({
      breakup: {
        error: 'breakup is not available',
      },
    });
    renderApp({ initialState });
    checkIfComponentIsEmpty();
  });

  test('should render gross entities on mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Gross Settlements')).toBeInTheDocument();
    expect(screen.getByText('Payment')).toBeInTheDocument();
    expect(screen.getByText('Reversal')).toBeInTheDocument();
  });

  test('should toggle deductions view', async () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Payment')).toBeInTheDocument();
    expect(screen.getByText('Reversal')).toBeInTheDocument();
    const toggleBtn = screen.getByTestId('chevron-up');
    expect(toggleBtn).toBeInTheDocument();
    userEvent.click(toggleBtn);
    await waitFor(() => {
      expect(screen.getByTestId('chevron-down')).toBeInTheDocument();
      expect(screen.queryByText('Payment')).not.toBeInTheDocument();
      expect(screen.queryByText('Reversal')).not.toBeInTheDocument();
    });
  });
});
