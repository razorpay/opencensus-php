import Payment from 'merchant/models/Payment';
import { set, merge } from 'rzp/utils/immutable';

const PAYMENT_FETCH = 'PAYMENT_FETCH';
const PAYMENT_FETCH_CARD_DETAILS = 'PAYMENT_FETCH_CARD_DETAILS';
const PAYMENT_FETCH_REFUNDS = 'PAYMENT_FETCH_REFUNDS';
const PAYMENT_FETCH_TRANSFERS = 'PAYMENT_FETCH_TRANSFERS';
const PAYMENT_FETCH_BANK_TRANSFER = 'PAYMENT_FETCH_BANK_TRANSFER';
const PAYMENT_CAPTURE = 'PAYMENT_CAPTURE';
const PAYMENT_REFUND = 'PAYMENT_REFUND';
const PAYMENT_RESET = 'PAYMENT_RESET';

export const fetchItem = id => {
  let payment = new Payment();

  return {
    type: PAYMENT_FETCH,
    payload: payment.fetch(id),
  };
};

export const fetchCardDetails = payment => {
  return {
    type: PAYMENT_FETCH_CARD_DETAILS,
    payload: payment.fetchCardDetails(),
  };
};

export const fetchRefunds = payment => {
  return {
    type: PAYMENT_FETCH_REFUNDS,
    payload: payment.fetchRefunds(),
  };
};

export const fetchTransfers = payment => {
  return {
    type: PAYMENT_FETCH_TRANSFERS,
    payload: payment.fetchTransfers(),
  };
};

export const capturePayment = payment => {
  return {
    type: PAYMENT_CAPTURE,
    payload: payment.capture(),
  };
};

export const fetchBankTransfer = payment => {
  return {
    type: PAYMENT_FETCH_BANK_TRANSFER,
    payload: payment.fetchBankTransfer(),
  };
};

export const refundPayment = (payment, data) => {
  return {
    type: PAYMENT_REFUND,
    payload: payment.refund(data),
  };
};

export const resetPayment = () => {
  return {
    type: PAYMENT_RESET,
    payload: null,
  };
};

let initialState = {
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
  error: null,
};

export default function(state = initialState, action) {
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
      return set(state, 'bankTransfer', {
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

    case `${PAYMENT_RESET}`:
      return initialState;

    default:
      return state;
  }
}
