import { render } from 'test-utils';
import SettlementInfo from 'merchant/views/Settlements/v2/components/SettlementInfo';

jest.mock('merchant/components/StatusLabel', () => ({
  ...jest.requireActual('merchant/components/StatusLabel'),
  SettlementStatusLabel: ({ status }) => <div data-testid="status-label">{status}</div>,
}));

jest.mock('common/ui/Time', () => ({
  __esModule: true,
  default: ({ value }) => <div data-testid="settlement-created-time">{value}</div>,
}));

jest.mock('common/ui/Amount', () => ({
  __esModule: true,
  default: ({ value }) => <div data-testid="settlement-amount">{value}</div>,
}));

jest.mock('merchant/views/Settlements/v2/util', () => ({
  ...jest.requireActual('merchant/views/Settlements/v2/util'),
  __esModule: true,
  customSettlementEnabled: (user) => user.enableCustomSettlements,
}));

jest.mock('merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider', () => ({
  __esModule: true,
  default: () => <div data-testid="payment-optimizer-provider" />,
}));

const defaultState = {
  session: { user: { enableCustomSettlements: false, merchant: { currency: 'INR' } } },
};
const enableCustomSettlementsState = {
  session: { user: { enableCustomSettlements: true, merchant: { currency: 'INR' } } },
};

const renderApp = ({ initialState = {} } = {}) => {
  return render(<SettlementInfo settlementId="test-settlement-id" />, {
    initialState: { ...defaultState, ...initialState },
  });
};

export { renderApp, defaultState, enableCustomSettlementsState };
