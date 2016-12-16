import Customer from 'merchant/models/Customer'
import { fromJS } from 'immutable'

const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH'
const CUSTOMER_CREATE = 'CUSTOMER_CREATE'
const CUSTOMER_EDIT = 'CUSTOMER_EDIT'
const CUSTOMER_DELETE = 'CUSTOMER_DELETE'
const HIGHLIGHT_CUSTOMER = 'HIGHLIGHT_CUSTOMER'
const REMOVE_HIGHLIGHT = 'REMOVE_HIGHLIGHT'

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

export const deleteCustomer = (params) => {
  return (dispatch) => {
    return new Customer(params).delete().then(() => {
      dispatch({
        type: CUSTOMER_DELETE,
        payload: customer
      })
    })
  }
}

export const highlightCustomerRow = (customer) => {
  return (dispatch) => {
    dispatch({
      type: HIGHLIGHT_CUSTOMER,
      payload: customer
    })

    setTimeout(() => {
      dispatch({
        type: REMOVE_HIGHLIGHT
      })
    }, 5000)
  }
}

let initialState = {
  loading: true,
  customers: [],
  count: 0,
  highlightRowId: null
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${CUSTOMERS_FETCH}::PENDING`:
      return state.merge({
        loading: true,
        highlightRowId: null
      })

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

    case `${CUSTOMER_DELETE}::SUCCESS`:
      return state.set('plans', state.get('plans').remove(action.payload))

    case HIGHLIGHT_CUSTOMER:
      return state.set('highlightRowId', action.payload.get('id'))

    case REMOVE_HIGHLIGHT:
      return state.set('highlightRowId', null)

    default:
      return state
  }
}
