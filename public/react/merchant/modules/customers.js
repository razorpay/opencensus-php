import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH'
const CUSTOMER_CREATE = 'CUSTOMER_CREATE'
const CUSTOMER_EDIT = 'CUSTOMER_EDIT'

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
    return dispatch({
      type: CUSTOMER_CREATE,
      payload: ajax({
        url: '/customer',
        method: 'post',
        data
      })
    })
  }
}

export const editCustomer = (id, data) => {
  return (dispatch) => {
    return dispatch({
      type: CUSTOMER_EDIT,
      payload: ajax({
        url: `/customer/${id}`,
        method: 'put',
        data
      })
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

    case `${CUSTOMER_CREATE}::SUCCESS`:
      return state.set('customers', state.get('customers').unshift(action.payload.data))

    case `${CUSTOMER_EDIT}::SUCCESS`:
      let customers = state.get('customers')
      return state.set('customers', customers.update(
        customers.findIndex((item) => item.get('id') === action.payload.data.id),
        (item) => item.merge(action.payload)
      ))

    default:
      return state
  }
}
