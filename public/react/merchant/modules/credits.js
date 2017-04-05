import ajax from 'merchant/utils/ajax'

const FETCH_ALL = 'FETCH_ALL'

const getCreditsData = () => {
  var params = {
    route_name: 'credits_fetch_multiple'
  };

  return ajax({
    url: '/user/generic',
    data: params,
    appendModeInQueryParam: true
  })
};

const fetchBalance = () => {
  var params = {
    route_name: 'balance_fetch'
  };

  return ajax({
    url: '/user/generic',
    data: params,
    appendModeInQueryParam: true
  })
}

export const fetchCreditBalance = ()=> {
  return (dispatch) => {
    return dispatch({
      type: FETCH_ALL,
      payload: Promise.all([
        getCreditsData(),
        fetchBalance()
      ]).then(values=> {
        if (
          !values[0].success ||
          !values[1].success ||
          !Array.isArray(values[0].data.items)
        ) {
          throw "Couldn't load credits data";
        }
        return values;
      })
    })
  }
}

let initialState = {
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${FETCH_ALL}::SUCCESS`:
      return {
        ...state,
        creditsData: action.payload[0].data,
        balanceData: action.payload[1].data
      };

    case `${FETCH_ALL}::ERROR`:
      return {
        ...state,
        errorData: action.payload.errors
      };

    default:
      return state
  }
}
