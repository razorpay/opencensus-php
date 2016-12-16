import Customer from 'merchant/models/Customer'
import { fromJS } from 'immutable'

const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH'
const CUSTOMER_CREATE = 'CUSTOMER_CREATE'
const CUSTOMER_EDIT = 'CUSTOMER_EDIT'

export const fetchCustomers = (params) => {
  return (dispatch) => {
    return dispatch({
      type: CUSTOMERS_FETCH,
      payload: Customer.fetchAll(params)
    })
  }
}

export const saveCustomer = (params) => {
  return (dispatch) => {
    return dispatch({
      type: params.id ? CUSTOMER_EDIT : CUSTOMER_CREATE,
      payload: new Customer(params).save()
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
      return state.set('customers', state.get('customers').unshift(action.payload))

    case `${CUSTOMER_EDIT}::SUCCESS`:
      let customers = state.get('customers')
      return state.set('customers', customers.update(
        customers.findIndex((item) => item.get('id') === action.payload.get('id')),
        (item) => item.merge(action.payload)
      ))

    default:
      return state
  }
}
