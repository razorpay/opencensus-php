import Refund from 'merchant/models/Refund'
import { set, merge } from 'rzp/utils/immutable'

const REFUNDS_FETCH = 'REFUNDS_FETCH'

export const fetchRefunds = (params) => {
  return (dispatch) => {
    let refund = new Refund()
    return dispatch({
      type: REFUNDS_FETCH,
      payload: refund.fetchAll(params)
    })
  }
}

let initialState = {
  loading: true,
  refunds: [],
  count: 0,
  error: null,
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${REFUNDS_FETCH}::PENDING`:
      return set(state, 'loading', true)

    case `${REFUNDS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        refunds: action.payload.data.items,
        count: action.payload.data.count,
        error: null,
      })

    case `${REFUNDS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
      })

    default:
      return state
  }
}
