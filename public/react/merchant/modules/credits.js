import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';

const FETCH_BALANCE_AND_CREDITS = 'FETCH_BALANCE_AND_CREDITS';

const getCreditsData = () => {
  var params = {
    route_name: 'credits_fetch_multiple',
  };

  return ajax({
    url: '/user/generic',
    data: params,
    appendModeInQueryParam: true,
  });
};

const fetchBalance = () => {
  var params = {
    route_name: 'balance_fetch',
  };

  return ajax({
    url: '/user/generic',
    data: params,
    appendModeInQueryParam: true,
  });
};

export const fetchCreditBalance = () => {
  return dispatch => {
    return dispatch({
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
    });
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

    case `${FETCH_BALANCE_AND_CREDITS}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
