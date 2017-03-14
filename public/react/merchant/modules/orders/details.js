import Order from 'merchant/models/Order'
import { set, merge } from 'rzp/utils/immutable'

const ORDER_FETCH = 'ORDER_FETCH'
const ORDER_PAYMENTS_FETCH = 'ORDER_PAYMENTS_FETCH'

export const fetchOrder = (id) => {
  return (dispatch) => {
    return dispatch({
      type: ORDER_FETCH,
      payload: Order.fetch(id)
    })
  }
}

export const fetchOrderPayments = (order) => {
  return (dispatch) => {
    return dispatch({
      type: ORDER_PAYMENTS_FETCH,
      payload: order.fetchPayments()
    })
  }
}

let initialState = {
  loading: true,
  order: {},
  error: null,
  payments: {
    loading: true,
    items: [],
    error: null,
  },
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${ORDER_FETCH}::PENDING`:
      return set(state, 'loading', true)

    case `${ORDER_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        order: action.payload,
        error: null
      })

    case `${ORDER_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        order: initialState.order
      })

    case `${ORDER_PAYMENTS_FETCH}::PENDING`:
      return set(state, 'payments', {
        loading: true,
        items: [],
        error: null
      })

    case `${ORDER_PAYMENTS_FETCH}::SUCCESS`:
      return set(state, 'payments', {
        loading: false,
        items: action.payload.data.items,
        error: null
      })

    case `${ORDER_PAYMENTS_FETCH}::ERROR`:
      return set(state, 'payments', {
        loading: false,
        items: [],
        error: action.payload.errors
      })

    default:
      return state
  }
}
