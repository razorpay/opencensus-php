import { merchantFetch } from 'merchantLA/utils/ajax';
import { merge, set } from 'rzp/utils/immutable';

const FETCH_BALANCE_AND_CREDITS = 'FETCH_BALANCE_AND_CREDITS';
const FETCH_BALANCE = 'FETCH_BALANCE';

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

let initialState = {
  loading: true,
  creditsData: {
    items: [],
  },
  balanceData: {},
  error: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_BALANCE_AND_CREDITS}::PENDING`:
      return set(state, 'loading', true);

    case `${FETCH_BALANCE_AND_CREDITS}::SUCCESS`:
      return merge(state, {
        loading: false,
        creditsData: action.payload[0].data,
        balanceData: action.payload[1].data,
        error: null,
      });

    case `${FETCH_BALANCE}::SUCCESS`:
      return merge(state, {
        loading: false,
        balanceData: action.payload.data,
        error: null,
      });

    case `${FETCH_BALANCE_AND_CREDITS}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
