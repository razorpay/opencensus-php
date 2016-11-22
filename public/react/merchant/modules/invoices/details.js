import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const INVOICE_FETCH = 'INVOICE_FETCH'

export const fetchInvoice = (id) => {
  return (dispatch) => {
    return dispatch({
      type: INVOICE_FETCH,
      payload: ajax({
        url: `/invoices/${id}`
      })
    })
  }
}

let initialState = {
  loading: true,
  invoice: {}
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${INVOICE_FETCH}::PENDING`:
      return state.set('loading', true)

    case `${INVOICE_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        invoice: action.payload.data.items[0]
      })

    case `${INVOICE_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error,
        invoice: {}
      })

    default:
      return state
  }
}
