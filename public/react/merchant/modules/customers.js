import { set, merge, unshift } from 'rzp/utils/immutable'
import Customer from 'merchant/models/Customer'

const CUSTOMERS_FETCH = 'CUSTOMERS_FETCH'
const CUSTOMERS_AUTOCOMPLETE_FETCH = 'CUSTOMERS_AUTOCOMPLETE_FETCH'
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

export const fetchCustomersForAutocomplete = () => {
  return (dispatch) => {
    return dispatch({
      type: CUSTOMERS_AUTOCOMPLETE_FETCH,
      payload: Customer.fetchForAutocomplete()
    })
  }
}

export const saveCustomer = (customer) => {
  return (dispatch) => {
    return dispatch({
      type: customer.isNew ? CUSTOMER_CREATE : CUSTOMER_EDIT,
      payload: customer.save()
    })
  }
}

export const deleteCustomer = (customer) => {
  return (dispatch) => {
    return customer.delete().then(() => {
      dispatch({
        type: CUSTOMER_DELETE,
        payload: params
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

export default function (state = initialState, action) {
  switch(action.type) {
    case `${CUSTOMERS_FETCH}::PENDING`:
    case `${CUSTOMERS_AUTOCOMPLETE_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        highlightRowId: null
      })

    case `${CUSTOMERS_FETCH}::SUCCESS`:
    case `${CUSTOMERS_AUTOCOMPLETE_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        customers: action.payload.data.items,
        count: action.payload.data.count
      })

    case `${CUSTOMERS_FETCH}::ERROR`:
    case `${CUSTOMERS_AUTOCOMPLETE_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error
      })

    case `${CUSTOMER_CREATE}::SUCCESS`:
      return set(state, 'customers', unshift(state.customers, action.payload))

    case `${CUSTOMER_EDIT}::SUCCESS`:
      let customerIndex = state.customers.findIndex((item) => item.id === action.payload.id)
      return set(state, `customers.${customerIndex}`, action.payload)

    case `${CUSTOMER_DELETE}::SUCCESS`:
      // return set(state, 'customers', state.remove(state.customers, action.payload))

    case HIGHLIGHT_CUSTOMER:
      return set(state, 'highlightRowId', action.payload.id)

    case REMOVE_HIGHLIGHT:
      return set(state, 'highlightRowId', null)

    default:
      return state
  }
}
