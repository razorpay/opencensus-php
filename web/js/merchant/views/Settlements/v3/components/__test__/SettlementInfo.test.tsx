import store from 'merchant/store';
import SettlementInfo from 'merchant/views/Settlements/v3/components/SettlementInfo';
import React from 'react';
import { render, screen } from 'test-utils';

jest.mock('common/ui/Clipboard/Custom', () => ({ children }) => (
  <>
    <div>Custom Clipboard</div>
    <div>{children}</div>
  </>
));

const globalStore = store.getState();

const getInitialState = ({ settlement = {} }) => {
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
    },
  };
};

const renderApp = ({ initialState }) =>
  render(<SettlementInfo />, {
    initialState,
  });

describe('SettlementInfo', () => {
  test.each([
    { title: 'Settlement ID', value: 'setl_JCVHSjHRi9QHto' },
    { title: 'UTR number', value: 'cg2mpl08cfbf3p7nghfg' },
    { title: 'Status', value: 'Processed' },
  ])('should render settlement details after getting info', ({ title, value }) => {
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText(title)).toBeInTheDocument();
    expect(screen.getByText(value)).toBeInTheDocument();
  });

  test.each([
    { title: 'Net settlement', key: 'amount' },
    { title: 'Status', key: 'status' },
    { title: 'UTR number', key: 'utr' },
  ])('should render empty dash if %s info value is null', ({ title, key }) => {
    const initialState = getInitialState({ settlement: { [key]: null } });
    renderApp({ initialState });
    expect(screen.getByText(title)).toBeInTheDocument();
    expect(screen.getByText('---')).toBeInTheDocument();
  });
});
