import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';

// graph data
// fetched everytime date is changed
const ANALYTICS_FETCH = 'ANALYTICS_FETCH';

// numbers apart from graph
const ENTITY_TOTALS_FETCH = 'ENTITY_TOTALS_FETCH';
const PAYMENT_BREAKUP_FETCH = 'PAYMENT_BREAKUP_FETCH';
const CURRENT_BALANCE_FETCH = 'CURRENT_BALANCE_FETCH';

const intervals = [
  {
    value: 'day',
    label: 'Daily',
  },
  {
    value: 'week',
    label: 'Weekly',
  },
  {
    value: 'month',
    label: 'Monthly',
  },
  {
    value: 'year',
    label: 'Yearly',
  },
];

let initialState = {
  analytics: {
    loading: true,
    data: [],
    error: null,
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

export default function(state = initialState, action) {
  /* TODO: handle failure cases */
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
          data: action.payload.data,
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
        data: initialState.analytics.data,
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

export const fetchAnalytics = state => {
  return dispatch => {
    return dispatch({
      type: ANALYTICS_FETCH,
      payload: ajax({
        url: '/analytics/transactions',
        data: {
          type: intervals[state.interval].value,
          from: state.from.unix(),
          to: state.to.unix(),
        },
      }),
    });
  };
};

export const fetchEntityTotals = () => {
  return dispatch => {
    return dispatch({
      type: ENTITY_TOTALS_FETCH,
      payload: ajax('/analytics/aggregations'),
    });
  };
};

export const fetchPaymentBreakup = () => {
  return dispatch => {
    return dispatch({
      type: PAYMENT_BREAKUP_FETCH,
      payload: ajax('/analytics/payment/aggregations'),
    });
  };
};

export const fetchCurrentBalance = () => {
  return dispatch => {
    return dispatch({
      type: CURRENT_BALANCE_FETCH,
      payload: ajax('/user/generic', {
        appendModeInQueryParam: true,
        data: {
          route_name: 'balance_fetch',
        },
      }),
    });
  };
};
