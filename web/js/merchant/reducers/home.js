import ajax from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';
import { createLineData } from 'common/utils/chart/index.js';
import { merchantFetch } from 'merchant/utils/ajax';

// graph data
// fetched everytime date is changed
const ANALYTICS_FETCH = 'ANALYTICS_FETCH';

// numbers apart from graph
const ENTITY_TOTALS_FETCH = 'ENTITY_TOTALS_FETCH';
const PAYMENT_BREAKUP_FETCH = 'PAYMENT_BREAKUP_FETCH';
const CURRENT_BALANCE_FETCH = 'CURRENT_BALANCE_FETCH';
const SETTLEMENT_AMOUNT_FETCH = 'SETTLEMENT_AMOUNT_FETCH';

// Instant activation actions
const SHOW_IA_SUCCESS = 'SHOW_IA_SUCCESS';
const SHOW_KYC_SUCCESS = 'SHOW_KYC_SUCCESS';
const HIDE_KYC_SUCCESS = 'HIDE_KYC_SUCCESS';
const SHOW_KYC_DETAILS = 'SHOW_KYC_DETAILS';
const HIDE_KYC_DETAILS = 'HIDE_KYC_DETAILS';
const SHOW_ACCEPT_PAYMENTS = 'SHOW_ACCEPT_PAYMENTS';
const HIDE_ACCEPT_PAYMENTS = 'HIDE_ACCEPT_PAYMENTS';
const SHOW_PRODUCTS = 'SHOW_PRODUCTS';
const HIDE_PRODUCTS = 'HIDE_PRODUCTS';
const SHOW_PAN_STATUS_MODAL = 'SHOW_PAN_STATUS_MODAL';
const HIDE_PAN_STATUS_MODAL = 'HIDE_PAN_STATUS_MODAL';

let initialState = {
  analytics: {
    loading: true,
    data: [],
    transaction_count: null,
    transaction_amount: null,
  },
  entity_totals: {
    loading: true,
    data: {},
    error: null,
  },
  payment_breakup: {
    loading: true,
    data: {},
    error: null,
  },
  current_balance: {
    loading: true,
    data: {},
    error: null,
  },
  settlement_amount: {
    loading: true,
    data: {},
    error: null,
  },
  instantActivations: {
    showKYCActivationSuccess: false,
    showInstantActivationSuccess: false,
    showKYCDetails: false,
    showAcceptPayments: false,
    showProductsModal: false,
    showPANStatus: false,
  },
};

const getTransactionCountData = (data, mode) => {
  return createLineData(
    data.filter(d => d.mode === mode),
    'count',
    'Successful Transactions'
  );
};

const getTransactionAmountData = (data, mode) => {
  data = JSON.parse(JSON.stringify(data));
  data = data.filter(d => {
    d.amount = d.amount / 100;
    return d.mode === mode;
  });
  return createLineData(data, 'amount', 'Transaction Volume');
};

export const closeOnboardingStep = _ => {
  return {
    type: 'CLOSE_ONBOARDING_STEP',
  };
};

export const fetchAnalytics = params => {
  return {
    type: ANALYTICS_FETCH,
    payload: ajax({
      url: '/analytics/transactions',
      data: {
        type: 'day',
        from: params.from,
        to: params.to,
      },
    }).then(response => {
      let transaction_count = null,
        transaction_amount = null;
      if (response.data) {
        transaction_count = getTransactionCountData(response.data, params.mode);
        transaction_amount = getTransactionAmountData(
          response.data,
          params.mode
        );
      }
      return {
        transaction_count,
        transaction_amount,
      };
    }),
  };
};

export const fetchEntityTotals = () => {
  return {
    type: ENTITY_TOTALS_FETCH,
    payload: ajax('/analytics/aggregations'),
  };
};

export const fetchPaymentBreakup = () => {
  return {
    type: PAYMENT_BREAKUP_FETCH,
    payload: ajax('/analytics/payment/aggregations'),
  };
};

export const fetchCurrentBalance = () => {
  return {
    type: CURRENT_BALANCE_FETCH,
    payload: merchantFetch('balance'),
  };
};

export const showInstantActivationSuccessModal = () => {
  return {
    type: SHOW_IA_SUCCESS,
  };
};

export const showKYCActivationSuccessModal = () => {
  return {
    type: SHOW_KYC_SUCCESS,
  };
};

export const hideKYCActivationSuccessModal = () => {
  return {
    type: HIDE_KYC_SUCCESS,
  };
};

export const showKYCDetailsModal = () => ({
  type: SHOW_KYC_DETAILS,
});

export const hideKYCDetailsModal = () => ({
  type: HIDE_KYC_DETAILS,
});

export const showAcceptPaymentsModal = () => ({
  type: SHOW_ACCEPT_PAYMENTS,
});

export const hideAcceptPaymentsModal = () => ({
  type: HIDE_ACCEPT_PAYMENTS,
});

export const showProductsModal = () => {
  return {
    type: SHOW_PRODUCTS,
  };
};

export const hideProductsModal = () => {
  return {
    type: HIDE_PRODUCTS,
  };
};

export const showPANStatusModal = () => {
  return {
    type: SHOW_PAN_STATUS_MODAL,
  };
};

export const hidePANStatusModal = () => {
  return {
    type: HIDE_PAN_STATUS_MODAL,
  };
};

export const fetchSettlementAmount = () => {
  return {
    type: SETTLEMENT_AMOUNT_FETCH,
    payload: merchantFetch('settlements/amount'),
  };
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${ANALYTICS_FETCH}::PENDING`:
      return set(state, 'analytics', initialState.analytics);

    case `${ENTITY_TOTALS_FETCH}::PENDING`:
      return set(state, 'entity_totals', initialState.entity_totals);

    case `${PAYMENT_BREAKUP_FETCH}::PENDING`:
      return set(state, 'payment_breakup', initialState.payment_breakup);

    case `${CURRENT_BALANCE_FETCH}::PENDING`:
      return set(state, 'current_balance', initialState.current_balance);

    case `${ANALYTICS_FETCH}::SUCCESS`:
      return merge(state, {
        analytics: {
          transaction_count: action.payload.transaction_count,
          transaction_amount: action.payload.transaction_amount,
          loading: false,
          error: null,
        },
      });

    case `${ENTITY_TOTALS_FETCH}::SUCCESS`:
      return merge(state, {
        entity_totals: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${PAYMENT_BREAKUP_FETCH}::SUCCESS`:
      return merge(state, {
        payment_breakup: {
          data: action.payload.data || initialState.payment_breakup.data,
          loading: false,
          error: null,
        },
      });

    case `${CURRENT_BALANCE_FETCH}::SUCCESS`:
      return merge(state, {
        current_balance: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${ANALYTICS_FETCH}::ERROR`:
      return set(state, 'analytics', {
        loading: false,
        error: action.payload.errors,
        transaction_count: initialState.analytics.transaction_count,
        transaction_amount: initialState.analytics.transaction_amount,
      });

    case `${ENTITY_TOTALS_FETCH}::ERROR`:
      return set(state, 'entity_totals', {
        loading: false,
        error: action.payload.errors,
        data: initialState.entity_totals.data,
      });

    case `${PAYMENT_BREAKUP_FETCH}::ERROR`:
      return set(state, 'payment_breakup', {
        loading: false,
        error: action.payload.errors,
        data: initialState.payment_breakup.data,
      });

    case `${CURRENT_BALANCE_FETCH}::ERROR`:
      return set(state, 'current_balance', {
        loading: false,
        error: action.payload.errors,
        data: initialState.current_balance.data,
      });

    case `${SETTLEMENT_AMOUNT_FETCH}::SUCCESS`:
      return merge(state, {
        settlement_amount: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${SETTLEMENT_AMOUNT_FETCH}::ERROR`:
      return set(state, 'settlement_amount', {
        loading: false,
        error: action.payload.errors,
        data: initialState.settlement_amount.data,
      });

    case `SHOW_IA_SUCCESS`:
      return set(state, 'instantActivations', {
        showInstantActivationSuccess: true,
      });

    case `SHOW_KYC_SUCCESS`:
      return set(state, 'instantActivations', {
        showKYCActivationSuccess: true,
      });

    case `HIDE_KYC_SUCCESS`:
      return set(state, 'instantActivations', {
        showKYCActivationSuccess: false,
      });

    case `SHOW_KYC_DETAILS`:
      return set(state, 'instantActivations', {
        showKYCDetails: true,
      });

    case `HIDE_KYC_DETAILS`:
      return set(state, 'instantActivations', {
        showKYCDetails: false,
      });

    case `SHOW_ACCEPT_PAYMENTS`:
      return set(state, 'instantActivations', {
        showAcceptPayments: true,
      });

    case `HIDE_ACCEPT_PAYMENTS`:
      return set(state, 'instantActivations', {
        showAcceptPayments: false,
      });

    case SHOW_PRODUCTS:
      return set(state, 'instantActivations', {
        showProductsModal: true,
      });

    case HIDE_PRODUCTS:
      return set(state, 'instantActivations', {
        showProductsModal: false,
      });

    case 'CLOSE_ONBOARDING_STEP':
      return set(state, 'closeOnboardingStep', true);

    case SHOW_PAN_STATUS_MODAL:
      return set(state, 'instantActivations', {
        showPANStatus: true,
      });

    case HIDE_PAN_STATUS_MODAL:
      return set(state, 'instantActivations', {
        showPANStatus: false,
      });

    default:
      return state;
  }
}
