import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const INVOICES_FETCH = 'INVOICES_FETCH'

export const fetchInvoices = () => {
  return (dispatch) => {
    return dispatch({
      type: INVOICES_FETCH,
      payload: ajax('/invoices')
    })
  }
}

let initialState = {
  loading: true,
  invoices: [],
  count: 0
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${INVOICES_FETCH}::PENDING`:
      return state.set('loading', true)

    case `${INVOICES_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        invoices: action.payload.data.items,
        count: action.payload.data.count
      })

    case `${INVOICES_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error
      })

    default:
      return state
  }
}
