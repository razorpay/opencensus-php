import Payment from 'merchant/models/Payment';
import { set, merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';

const PAYMENT_FETCH = 'PAYMENT_FETCH';
const PAYMENT_FETCH_CARD_DETAILS = 'PAYMENT_FETCH_CARD_DETAILS';
const PAYMENT_FETCH_REFUNDS = 'PAYMENT_FETCH_REFUNDS';
const PAYMENT_FETCH_TRANSFERS = 'PAYMENT_FETCH_TRANSFERS';
const PAYMENT_FETCH_BANK_TRANSFER = 'PAYMENT_FETCH_BANK_TRANSFER';
const PAYMENT_FETCH_UPI_TRANSFER = 'PAYMENT_FETCH_UPI_TRANSFER';
const PAYMENT_CAPTURE = 'PAYMENT_CAPTURE';
const PAYMENT_REFUND = 'PAYMENT_REFUND';
const PAYMENT_RESET = 'PAYMENT_RESET';
const CURRENT_BALANCE_FETCH = 'CURRENT_BALANCE_FETCH';
const FETCH_REFUND_FEE = 'FETCH_REFUND_FEE';
const MERCHANT_MANUAL_PAYMENT_ACTION = 'MERCHANT_MANUAL_PAYMENT_ACTION';
const FETCH_FAILURE_ANALYSIS = 'FETCH_FAILURE_ANALYSIS';
const RESET_FAILURE_ANALYSIS = 'RESET_FAILURE_ANALYSIS';

export const fetchItem = (id) => {
  const payment = new Payment();

  return {
    type: PAYMENT_FETCH,
    payload: payment.fetch(
      id,
      {},
      {
        expand: ['card', 'emi_plan', 'disputes', 'transaction', 'transaction.settlement'],
        dashboard_flag: ['refund_create_data'],
      },
    ),
  };
};

export const fetchCardDetails = (payment) => {
  return {
    type: PAYMENT_FETCH_CARD_DETAILS,
    payload: payment.fetchCardDetails(),
  };
};

export const fetchRefunds = (payment) => {
  return {
    type: PAYMENT_FETCH_REFUNDS,
    payload: payment.fetchRefunds(),
  };
};

export const fetchTransfers = (payment) => {
  return {
    type: PAYMENT_FETCH_TRANSFERS,
    payload: payment.fetchTransfers(),
  };
};

export const capturePayment = (payment) => {
  return {
    type: PAYMENT_CAPTURE,
    payload: payment.capture(),
  };
};

export const fetchBankTransfer = (payment) => {
  return {
    type: PAYMENT_FETCH_BANK_TRANSFER,
    payload: payment.fetchBankTransfer(),
  };
};

export const fetchUPITransfer = (payment) => {
  return {
    type: PAYMENT_FETCH_UPI_TRANSFER,
    payload: payment.fetchUPITransfer(),
  };
};

export const refundPayment = (payment, data) => {
  return {
    type: PAYMENT_REFUND,
    payload: payment.refund(data),
  };
};

export const createTransfer = ({ id, ...data }) => {
  const payment = new Payment({ id });
  return payment.transfer(data);
};

export const createDirectTransfer = (data) => {
  return merchantFetch({
    url: 'transfers',
    method: 'post',
    data,
  });
};

export const resetPayment = () => {
  return {
    type: PAYMENT_RESET,
    payload: null,
  };
};

export const fetchCurrentBalance = () => {
  return {
    type: CURRENT_BALANCE_FETCH,
    payload: merchantFetch('balance'),
  };
};

export const fetchRefundFee = (payment, amount) => {
  return {
    type: FETCH_REFUND_FEE,
    payload: payment.fetchInstantRefundFee(payment.id, amount),
  };
};

export const fetchFA = (data) => {
  return {
    type: FETCH_FAILURE_ANALYSIS,
    payload: merchantFetch(data),
  };
};

export const resetFA = () => {
  return {
    type: RESET_FAILURE_ANALYSIS,
  };
};

export const fetchMerchantManualAction = (payment_id) => {
  return {
    type: MERCHANT_MANUAL_PAYMENT_ACTION,
    payload: merchantFetch(`payment/${payment_id}/merchant/actions`),
  };
};

const initialState = {
  loading: true,
  payment: {
    notes: {},
  },
  card: {
    loading: false,
    details: {},
    error: null,
  },
  refunds: {
    loading: false,
    items: [],
    error: null,
  },
  refundFee: {
    loading: true,
    data: {},
    error: null,
  },
  current_balance: {
    loading: true,
    data: {},
    error: null,
  },
  transfers: {
    loading: false,
    items: [],
    error: null,
  },
  bankTransfer: {
    loading: false,
    details: {},
    error: null,
  },
  upiTransfer: {
    loading: false,
    details: {},
    error: null,
  },
  merchantManualAction: {
    loading: true,
    details: {},
    error: null,
  },
  failureAnalysisData: {
    loading: false,
    data: null,
    error: null,
  },
  error: null,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${PAYMENT_FETCH}::PENDING`:
    case `${PAYMENT_CAPTURE}::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${PAYMENT_FETCH}::SUCCESS`:
    case `${PAYMENT_CAPTURE}::SUCCESS`:
      return merge(state, {
        loading: false,
        payment: action.payload,
        error: null,
      });

    case `${PAYMENT_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        payment: initialState.payment,
      });

    case `${PAYMENT_FETCH_CARD_DETAILS}::PENDING`:
      return set(state, 'card', {
        loading: true,
        details: {},
        error: null,
      });

    case `${PAYMENT_FETCH_CARD_DETAILS}::SUCCESS`:
      return set(state, 'card', {
        loading: false,
        details: action.payload.data,
        error: null,
      });

    case `${PAYMENT_FETCH_CARD_DETAILS}::ERROR`:
      return set(state, 'card', {
        loading: false,
        details: {},
        error: action.payload.errors,
      });

    case `${PAYMENT_FETCH_BANK_TRANSFER}::PENDING`:
      return set(state, 'bankTransfer', {
        loading: true,
        details: {},
        error: null,
      });

    case `${PAYMENT_FETCH_BANK_TRANSFER}::SUCCESS`:
      return set(state, 'bankTransfer', {
        loading: false,
        details: action.payload.data,
        error: null,
      });

    case `${PAYMENT_FETCH_BANK_TRANSFER}::ERROR`:
      return set(state, 'upiTransfer', {
        loading: false,
        details: {},
        error: action.payload.errors,
      });

    case `${PAYMENT_FETCH_UPI_TRANSFER}::PENDING`:
      return set(state, 'upiTransfer', {
        loading: true,
        details: {},
        error: null,
      });

    case `${PAYMENT_FETCH_UPI_TRANSFER}::SUCCESS`:
      return set(state, 'upiTransfer', {
        loading: false,
        details: action.payload.data,
        error: null,
      });

    case `${PAYMENT_FETCH_UPI_TRANSFER}::ERROR`:
      return set(state, 'upiTransfer', {
        loading: false,
        details: {},
        error: action.payload.errors,
      });

    case `${PAYMENT_FETCH_REFUNDS}::PENDING`:
      return set(state, 'refunds', {
        loading: true,
        items: [],
        error: null,
      });

    case `${PAYMENT_FETCH_REFUNDS}::SUCCESS`:
      return set(state, 'refunds', {
        loading: false,
        items: action.payload.data.items,
        error: null,
      });

    case `${PAYMENT_FETCH_REFUNDS}::ERROR`:
      return set(state, 'refunds', {
        loading: false,
        items: [],
        error: action.payload.errors,
      });

    case `${FETCH_REFUND_FEE}::PENDING`:
      return set(state, 'refundFee', {
        loading: true,
        data: [],
        error: null,
      });

    case `${FETCH_REFUND_FEE}::SUCCESS`:
      return set(state, 'refundFee', {
        data: action.payload.data,
        loading: false,
        error: null,
      });

    case `${FETCH_REFUND_FEE}::ERROR`:
      return set(state, 'refundFee', {
        loading: false,
        error: action.payload.errors,
        data: {},
      });

    case `${PAYMENT_FETCH_TRANSFERS}::PENDING`:
      return set(state, 'transfers', {
        loading: true,
        items: [],
        error: null,
      });

    case `${PAYMENT_FETCH_TRANSFERS}::SUCCESS`:
      return set(state, 'transfers', {
        loading: false,
        items: action.payload.data.items,
        error: null,
      });

    case `${PAYMENT_FETCH_TRANSFERS}::ERROR`:
      return set(state, 'transfers', {
        loading: false,
        items: [],
        error: action.payload.errors,
      });

    case `${CURRENT_BALANCE_FETCH}::PENDING`:
      return set(state, 'current_balance', initialState.current_balance);

    case `${CURRENT_BALANCE_FETCH}::SUCCESS`:
      return merge(state, {
        current_balance: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });
    case `${CURRENT_BALANCE_FETCH}::ERROR`:
      return set(state, 'current_balance', {
        loading: false,
        error: action.payload.errors,
        data: initialState.current_balance.data,
      });

    case `${MERCHANT_MANUAL_PAYMENT_ACTION}::SUCCESS`:
      return merge(state, {
        merchantManualAction: {
          loading: false,
          details: action.payload.data,
          error: null,
        },
      });

    case `${MERCHANT_MANUAL_PAYMENT_ACTION}::ERROR`:
      return set(state, 'merchantManualAction', {
        loading: false,
        details: {},
        error: action.payload.errors,
      });

    case `${FETCH_FAILURE_ANALYSIS}::PENDING`:
      return set(state, 'failureAnalysisData', {
        loading: true,
        data: null,
        error: null,
      });

    case `${FETCH_FAILURE_ANALYSIS}::SUCCESS`:
      return set(state, 'failureAnalysisData', {
        data: action.payload.data,
        loading: false,
        error: null,
      });

    case `${FETCH_FAILURE_ANALYSIS}::ERROR`:
      return set(state, 'failureAnalysisData', {
        loading: false,
        error: action.payload.errors,
        data: null,
      });

    case RESET_FAILURE_ANALYSIS:
      return set(state, 'failureAnalysisData', initialState.failureAnalysisData);

    case PAYMENT_RESET:
      // maintaining the failure analysis data even in reset
      return {
        ...initialState,
        failureAnalysisData: state.failureAnalysisData,
      };

    default:
      return state;
  }
};
