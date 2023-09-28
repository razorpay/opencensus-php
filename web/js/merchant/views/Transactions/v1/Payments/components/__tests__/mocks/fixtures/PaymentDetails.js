import PaymentDetails from 'merchant/views/Transactions/v1/Payments/components/PaymentDetails';
import { createMemoryHistory } from 'history';
import { Router } from 'react-router-dom';

jest.mock('merchant/views/Transactions/v1/Payments/components/PaymentPageDetails', () => () => (
  <div>PaymentPageDetails</div>
));

jest.mock('merchant/views/Transactions/v1/Payments/components/OptimizerDetails', () => ({
  OptimizerDetails: ({ scrolledToBottom }) => (
    <div>
      OptimizerDetails <span>scrolledToBottom: {String(scrolledToBottom)}</span>
    </div>
  ),
}));
// prettier-ignore
jest.mock(
  'merchant/views/Transactions/v1/Payments/components/PaymentTransfers',
  () => ({ onCreateTransfer }) => (
    <>
      <div>PaymentTransfers</div>
      <button type="button" onClick={onCreateTransfer}>
        Create Transfer
      </button>
    </>
  ),
);
jest.mock('merchant/views/Transactions/v1/Payments/components/PlatformFeeDetails', () => () => (
  <div>Platform Fee Details</div>
));
// prettier-ignore
jest.mock(
  'merchant/views/Settlements/components/SettlementInfo',
  () => ({
    handleSettlementGuideClick,
    trackContactSupport,
    trackKnowMore,
    trackSameDaySettlement,
    trackSettlementClose,
  }) => (
    <>
      <div>SettlementInfo</div>
      <button type="button" onClick={handleSettlementGuideClick}>
        Settlement Guide
      </button>
      <button type="button" onClick={trackContactSupport}>
        Contact Support
      </button>
      <button type="button" onClick={trackKnowMore}>
        Know More
      </button>
      <button type="button" onClick={trackSameDaySettlement}>
        Track Same Day Settlement
      </button>
      <button type="button" onClick={trackSettlementClose}>
        Track Settlement Close
      </button>
    </>
  ),
);

jest.mock('merchant/views/Transactions/v1/Payments/components/PaymentDisputes', () => () => (
  <div>PaymentDisputes</div>
));

jest.mock('merchant/components/Mask/Email', () => () => <div>Masked Email</div>);
jest.mock('merchant/components/Mask/Contact', () => () => <div>Masked Contact</div>);
// prettier-ignore
jest.mock(
  'merchant/views/Transactions/v1/Payments/components/PaymentMethod',
  () => ({ onUPIClick }) => (
    <>
      <div>PaymentMethod</div>
      <button type="button" onClick={onUPIClick}>
        UPI
      </button>
    </>
  ),
);
// prettier-ignore
jest.mock(
  'merchant/views/Transactions/v1/Payments/components/PaymentRefund',
  () => ({ onToggleClick }) => (
    <>
      <div>PaymentRefund</div>
      <button type="button" onClick={onToggleClick}>
        Refund Details Toggle
      </button>
    </>
  ),
);
// prettier-ignore
jest.mock(
  'merchant/views/Transactions/v1/Payments/components/PaymentReceipt',
  () => ({ onUpdateReferenceId }) => (
    <>
      <div>PaymentReceipt</div>
      <button type="button" onClick={onUpdateReferenceId}>
        Update Refrence Id
      </button>
    </>
  ),
);

beforeAll(() => {
  Object.defineProperty(HTMLElement.prototype, 'scrollHeight', {
    configurable: true,
    value: 600,
  });
  Object.defineProperty(HTMLElement.prototype, 'clientHeight', {
    configurable: true,
    value: 400,
  });
  window.rzpQ = {
    component: jest.fn(),
    merchantActions: () => ({
      initiated: jest.fn(),
      success: jest.fn(),
    }),
  };
});

export const defaultProps = {
  payment: {
    id: 'paymentID',
    analyticsPayload: jest.fn(),
    status: 'authorized',
    method: 'upi_transfer',
    notes: {
      noteKey1: 'Payment note for key 1',
    },
    error_code: 'payment error code',
    error_description: 'payment error description',
    error_source: 'payment error source',
    error_step: 'payment error step',
    error_reason: 'payment error reason',
    provider: 'payment provider',
    gateway_provider: 'payment gateway provider',
    transaction: 'payment transaction',
    optimizer_provider: 'Razorpay',
    description: 'payment description',
    disputes: {
      count: 2,
      items: [
        {
          id: 'qw1efe3dwf',
          status: 'open',
          phase: 'dispute',
        },
        {
          id: 'er2efe3dwf',
          status: 'closed',
        },
      ],
    },
    customer_fee: '123',
    customer_fee_gst: '12',
    order_id: 'paymentOrderID',
    invoice_id: 'paymentInvoiceID',
  },
  card: {},
  bankTransfer: {
    loading: true,
    details: {
      bank_reference: 'bank reference',
    },
  },
  upiTransfer: {},
  refunds: {},
  transfers: {
    loading: false,
    items: [{ transfer_type: 'platform' }],
  },
  isLoading: false,
  openRefundModal: jest.fn(),
  isRoleAllowedEdit: true,
  viewSettlementOverview: jest.fn(),
  user: {
    isSingleReconEnabled: true,
    isOptimizerEnabled: true,
    isUxRevampPhase2Enabled: true,
    isPaymentPageReceiptsEnabled: true,
    merchant: {
      currency: 'INR',
    },
  },
  org: {},
  terminalProviders: [],
  goToLink: jest.fn(),
  customSettlementLoading: true,
  adminAsMerchant: true,
  showCustomSettlDetails: true,
  bankSettleStatus: '',
  onClose: jest.fn(),
  confirmCapture: jest.fn(),
  onRefundDetailsToggleClick: jest.fn(),
  onUpdateReferenceId: jest.fn(),
};

export const App = (props) => {
  return <PaymentDetails {...defaultProps} {...props} />;
};

export const AppWithRouter = (props) => {
  const history = createMemoryHistory();
  const state = { fromHomePage: true };
  history.push('/', state);
  return (
    <Router history={history}>
      <PaymentDetails {...defaultProps} {...props} />
    </Router>
  );
};
