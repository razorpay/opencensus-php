import ajax from 'merchant/utils/ajax'

const CREDITS_FETCH = 'CREDITS_FETCH'
const BALANCE_FETCH = 'BALANCE_FETCH'


export const getCreditsData = () => {
  var params = {
    route_name: 'credits_fetch_multiple',
    mode: 'test'
  };

  return (dispatch) => {
    return dispatch({
      type: CREDITS_FETCH,
      payload: ajax('/user/generic', params)
    })
  }
};

export const fetchBalance = () => {
  var params = {
    route_name: 'balance_fetch',
    mode: 'test'
  };

  return (dispatch) => {
    return dispatch({
      type: BALANCE_FETCH,
      payload: ajax('/user/generic', params)
    })
  }
}

let initialState = {
  loading: true,
  subscriptions: [],
  count: 0
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${CREDITS_FETCH}::SUCCESS`:
      return state.merge({
        creditsData: action.payload.success ? action.payload : null,
      })

    case `${CREDITS_FETCH}::ERROR`:
      return state.merge({
        error: action.error
      })

    case `${BALANCE_FETCH}::SUCCESS`:
      return state.merge({
        balanceData: action.payload.success ? action.payload.data : null,
      })

    case `${BALANCE_FETCH}::ERROR`:
      return state.merge({
        error: action.error
      })

    default:
      return state
  }
}
