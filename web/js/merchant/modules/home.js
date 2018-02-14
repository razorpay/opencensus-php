import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';
import { createLineData } from 'rzp/utils/chart/index.js';
import { merchantFetch } from 'rzp/utils/ajax';

// graph data
// fetched everytime date is changed
const ANALYTICS_FETCH = 'ANALYTICS_FETCH';

// numbers apart from graph
const ENTITY_TOTALS_FETCH = 'ENTITY_TOTALS_FETCH';
const PAYMENT_BREAKUP_FETCH = 'PAYMENT_BREAKUP_FETCH';
const CURRENT_BALANCE_FETCH = 'CURRENT_BALANCE_FETCH';

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

    default:
      return state;
  }
}
