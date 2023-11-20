import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import store from 'merchant/store';
import SettlementInfo from 'merchant/views/Settlements/v3/components/SettlementInfo/SettlementInfoRevamp';
import { useMobile } from 'common/hooks/useMobile';

jest.mock('common/ui/Clipboard/Custom', () => ({ children }) => (
  <>
    <div>Custom Clipboard</div>
    <div>{children}</div>
  </>
));

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

const mockCopyToClipboard = jest.fn();

jest.mock('common/utils/copyToClipboard', () => ({
  __esModule: true,
  default: mockCopyToClipboard,
}));

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
describe('SettlementInfo revamp', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });
  test.each([
    { title: 'Settlement ID', value: 'setl_JCVHSjHRi9QHto' },
    { title: 'UTR number', value: 'cg2mpl08cfbf3p7nghfg' },
  ])('should render settlement details after getting info', ({ title, value }) => {
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText(title)).toBeInTheDocument();
    expect(screen.getByText(value)).toBeInTheDocument();
  });

  test.each([{ title: 'UTR number', key: 'utr' }])(
    'should render empty dash if %s info value is null',
    ({ title, key }) => {
      const initialState = getInitialState({ settlement: { [key]: null } });
      renderApp({ initialState });
      expect(screen.getByText(title)).toBeInTheDocument();
      expect(screen.getByText('generated after settlement gets processed')).toBeInTheDocument();
    },
  );

  test('should render settlement details after getting info on mobile', async () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Settlement ID')).toBeInTheDocument();
    expect(screen.getByText('setl_JCVHSjHRi9QHto')).toBeInTheDocument();
    const toggleInfoBtn = screen.getByTestId('collapsible-container');
    await userEvent.click(toggleInfoBtn);
    await waitFor(() => {
      expect(screen.queryByText('Settlement ID')).not.toBeInTheDocument();
      expect(screen.queryByText('setl_JCVHSjHRi9QHto')).not.toBeInTheDocument();
    });
  });
});
