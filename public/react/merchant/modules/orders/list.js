import Order from 'merchant/models/Order'
import { set, merge } from 'rzp/utils/immutable'

const ORDERS_FETCH = 'ORDERS_FETCH'

export const fetchOrders = (params) => {
  return (dispatch) => {
    return dispatch({
      type: ORDERS_FETCH,
      payload: Order.fetchAll(params)
    })
  }
}

let initialState = {
  loading: true,
  orders: [],
  count: 0,
  error: null,
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${ORDERS_FETCH}::PENDING`:
      return set(state, 'loading', true)

    case `${ORDERS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        orders: action.payload.data.items,
        count: action.payload.data.count,
        error: null,
      })

    case `${ORDERS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
      })

    default:
      return state
  }
}
