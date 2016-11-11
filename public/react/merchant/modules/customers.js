import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH'
const CUSTOMER_ADDED = 'CUSTOMER_ADDED'

export const fetchCustomers = () => {
  return (dispatch) => {
    return dispatch({
      type: CUSTOMERS_FETCH,
      payload: ajax('/customers')
    })
  }
}

export const createCustomer = (data) => {
  return (dispatch) => {
    return ajax({
      url: '/customer',
      method: 'post',
      data
    })
  }
}

export const customerAdded = (customer) => {
  return {
    type: CUSTOMER_ADDED,
    payload: customer
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

    case CUSTOMER_ADDED:
      return state.set('customers', state.get('customers').unshift(action.payload))

    default:
      return state
  }
}
