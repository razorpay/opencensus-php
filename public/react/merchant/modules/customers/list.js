import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH'

export const fetchCustomers = () => {
  return (dispatch) => {
    return dispatch({
      type: CUSTOMERS_FETCH,
      payload: ajax('/customers')
    })
  }
}

let initialState = {
  loading: true,
  customers: [],
  count: 0
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${CUSTOMERS_FETCH}::PENDING`:
      return state.set('loading', true)

    case `${CUSTOMERS_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        customers: action.payload.data.items,
        count: action.payload.data.count
      })

    case `${CUSTOMERS_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error
      })

    default:
      return state
  }
}
