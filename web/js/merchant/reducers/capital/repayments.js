import { merge } from 'common/utils/immutable';
import Repayments from 'merchant/models/Capital/Repayments';

const FETCH_REPAYMENTS = 'FETCH_REPAYMENTS';
const FETCH_BALANCES = 'FETCH_BALANCES';

export const fetchRepayments = (data) => {
  const repayment = new Repayments();

  return {
    type: FETCH_REPAYMENTS,
    payload: repayment.fetchRepayments(data),
  };
};

export const fetchBalances = (data) => {
  const repayment = new Repayments();

  return {
    type: FETCH_BALANCES,
    payload: repayment.fetchBalances(data),
  };
};

const getInitialState = () => {
  return {
    list: {
      loading: false,
      data: [],
      error: null,
    },
    installments: {
      loading: false,
      data: null,
      error: null,
    },
    balances: {
      loading: false,
      data: null,
      error: null,
    },
  };
};

const initialState = getInitialState();

export default function (state = initialState, action) {
  switch (action.type) {
    case `${FETCH_REPAYMENTS}::PENDING`:
      return merge(state, {
        list: {
          loading: true,
          data: [],
        },
      });

    case `${FETCH_REPAYMENTS}::SUCCESS`:
      return merge(state, {
        list: {
          loading: false,
          data: action.payload.data.repayments,
        },
      });

    case `${FETCH_REPAYMENTS}::ERROR`:
      return merge(state, {
        list: {
          loading: false,
          data: [],
          error: action.payload.errors,
        },
      });
    case `${FETCH_BALANCES}::PENDING`:
      return merge(state, {
        balances: {
          loading: true,
          data: [],
          error: null,
        },
      });

    case `${FETCH_BALANCES}::SUCCESS`:
      return merge(state, {
        balances: {
          loading: false,
          data: action.payload.data.balances,
          error: null,
        },
      });

    case `${FETCH_BALANCES}::ERROR`:
      return merge(state, {
        balances: {
          loading: false,
          data: [],
          error: action.payload.errors,
        },
      });

    default:
      return state;
  }
}
