import { merchantFetch } from 'merchantLA/utils/ajax';
import { merge, set } from 'common/utils/immutable';

const FETCH_BALANCE_AND_CREDITS = 'FETCH_BALANCE_AND_CREDITS';
const FETCH_BALANCE = 'FETCH_BALANCE';
const FETCH_CREDITS = 'FETCH_CREDITS';

const getCreditsData = _ => merchantFetch('credits');
const fetchBalance = _ => merchantFetch('balance');

export const fetchCreditBalance = () => {
  return {
    type: FETCH_BALANCE_AND_CREDITS,
    payload: Promise.all([getCreditsData(), fetchBalance()]).then(values => {
      if (
        !values[0].success ||
        !values[1].success ||
        !Array.isArray(values[0].data.items)
      ) {
        throw "Couldn't load credits data";
      }
      return values;
    }),
  };
};

export const fetchBalanceAction = () => {
  return {
    type: FETCH_BALANCE,
    payload: fetchBalance().then(values => {
      return values;
    }),
  };
};

export const fetchCreditsAction = () => {
  return {
    type: FETCH_CREDITS,
    payload: getCreditsData().then(values => {
      return values;
    }),
  };
};

let initialState = {
  loading: true,
  creditsData: {
    data: {
      items: [],
    },
    loading: false,
    error: null,
  },
  balanceData: {
    data: {},
    loading: false,
    error: null,
  },
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_BALANCE_AND_CREDITS}::PENDING`:
      return set(state, 'loading', true);

    case `${FETCH_BALANCE_AND_CREDITS}::SUCCESS`:
      return merge(state, {
        loading: false,
        creditsData: {
          data: action.payload[0].data,
          loading: false,
          error: null,
        },
        balanceData: {
          data: action.payload[1].data,
          loading: false,
          error: null,
        },
        error: null,
      });

    case `${FETCH_BALANCE_AND_CREDITS}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
      });

    // Balance
    case `${FETCH_BALANCE}::PENDING`:
      return set(state, 'balanceData.loading', true);

    case `${FETCH_BALANCE}::SUCCESS`:
      return merge(state, {
        ...state,
        balanceData: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${FETCH_BALANCE}::ERROR`:
      return merge(state, {
        balanceData: {
          ...state.balanceData,
          loading: false,
          error: action.payload.errors,
        },
      });

    // Credits
    case `${FETCH_CREDITS}::PENDING`:
      return set(state, 'creditsData.loading', true);

    case `${FETCH_CREDITS}::SUCCESS`:
      return merge(state, {
        ...state,
        creditsData: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${FETCH_CREDITS}::ERROR`:
      return merge(state, {
        creditsData: {
          ...state.creditsData,
          loading: false,
          error: action.payload.errors,
        },
      });

    default:
      return state;
  }
}
