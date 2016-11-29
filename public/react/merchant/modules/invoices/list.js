import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const INVOICES_FETCH = 'INVOICES_FETCH'
const INVOICE_CREATE = 'INVOICE_CREATE'
const APPEND_INVOICE_TO_LIST = 'APPEND_INVOICE_TO_LIST'

export const appendInvoiceToList = (invoice) => {
  return {
    type: APPEND_INVOICE_TO_LIST,
    payload: invoice
  }
}

export const fetchInvoices = (params) => {
  return (dispatch) => {
    return dispatch({
      type: INVOICES_FETCH,
      payload: ajax({
        url: '/invoices',
        data: params
      })
    })
  }
}

export const createInvoice = (invoice) => {
  return (dispatch) => {
    return dispatch({
      type: INVOICE_CREATE,
      payload: ajax({
        url: '/invoices',
        method: 'post',
        data: invoice
      })
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

    case `${INVOICE_CREATE}::SUCCESS`:
      return state.set('invoices', state.get('invoices').unshift(action.payload.data))

    default:
      return state
  }
}
