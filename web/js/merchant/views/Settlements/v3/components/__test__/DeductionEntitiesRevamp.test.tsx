import React from 'react';
import { checkIfComponentIsEmpty, render, screen, userEvent, waitFor } from 'test-utils';
import store from 'merchant/store';
import DeductionsEntities from 'merchant/views/Settlements/v3/components/DeductionsEntities/DeductionsEntitiesRevamp';
import { useMobile } from 'common/hooks/useMobile';
import * as SettlementsDB from 'merchant/views/Settlements/__test__/data/SettlementsDB';

const initBreakupDetails = SettlementsDB.settlementTabBreakupDetails;

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

const globalStore = store.getState();

const getInitialState = ({ breakup = {} } = {}) => {
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
      breakupDetails: { ...initBreakupDetails, ...breakup },
    },
  };
};

const renderApp = ({ initialState }) =>
  render(<DeductionsEntities />, {
    initialState,
  });
describe('DeductionsEntities revamp', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  test('should render deductions entities', () => {
    const initialState = getInitialState();
    renderApp({ initialState });
    expect(screen.getByText('Deductions')).toBeInTheDocument();
    expect(screen.getByText('Adjustment')).toBeInTheDocument();
    expect(screen.getByText('Transfer')).toBeInTheDocument();
  });

  test('should render able to change entity tab', async () => {
    const initialState = getInitialState();
    renderApp({ initialState });
    expect(screen.getByText('Deductions')).toBeInTheDocument();
    expect(screen.queryByText('Net deduction')).not.toBeInTheDocument();
    userEvent.click(screen.getByText('Adjustment'));
    await waitFor(() => {
      expect(screen.getByText('Net deduction')).toBeInTheDocument();
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

  test('should render deductions entities on mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    const initialState = getInitialState();
    renderApp({ initialState });
    expect(screen.getByText('Deductions')).toBeInTheDocument();
    expect(screen.getByText('Adjustment')).toBeInTheDocument();
    expect(screen.getByText('Transfer')).toBeInTheDocument();
  });

  test('should toggle deductions view', async () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    const initialState = getInitialState();
    renderApp({ initialState });
    expect(screen.getByText('Adjustment')).toBeInTheDocument();
    expect(screen.getByText('Transfer')).toBeInTheDocument();
    const toggleBtn = screen.getByTestId('chevron-up');
    expect(toggleBtn).toBeInTheDocument();
    userEvent.click(toggleBtn);
    await waitFor(() => {
      expect(screen.getByTestId('chevron-down')).toBeInTheDocument();
      expect(screen.queryByText('Adjustment')).not.toBeInTheDocument();
      expect(screen.queryByText('Transfer')).not.toBeInTheDocument();
    });
  });
});
