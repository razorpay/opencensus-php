import PaymentDetails from 'merchant/views/Transactions/Payments/components/PaymentDetails';
import { createMemoryHistory } from 'history';
import { Router } from 'react-router-dom';

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
  transfers: {},
  isLoading: false,
  openRefundModal: jest.fn(),
  isRoleAllowedEdit: true,
  viewSettlementOverview: jest.fn(),
  user: {
    isSingleReconEnabled: true,
    isOptimizerEnabled: true,
    isUxRevampPhase2Enabled: true,
    isPaymentPageReceiptsEnabled: true,
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
