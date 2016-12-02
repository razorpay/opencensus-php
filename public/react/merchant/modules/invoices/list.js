import Invoice from 'merchant/models/Invoice'
import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const INVOICES_FETCH = 'INVOICES_FETCH'
const INVOICE_CREATE = 'INVOICE_CREATE'
const HIGHLIGHT_INVOICE = 'HIGHLIGHT_INVOICE'

export const fetchInvoices = (params) => {
  return (dispatch) => {
    return dispatch({
      type: INVOICES_FETCH,
      payload: Invoice.fetchAll(params)
    })
  }
}

export const saveInvoice = (params) => {
  return (dispatch) => {
    return dispatch({
      type: params.id ? INVOICE_EDIT : INVOICE_CREATE,
      payload: new Invoice(params).save()
    })
  }
}

export const highLightInvoice = (invoiceId) => {
  return {
    type: HIGHLIGHT_INVOICE,
    invoiceId
  }
}

let initialState = {
  loading: true,
  invoices: [],
  count: 0,
  highLightInvoiceId: null
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${INVOICES_FETCH}::PENDING`:
      return state.merge({
        loading: true,
        highLightInvoiceId: null
      })

    case `${INVOICES_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        invoices: action.payload.data.items,
        count: action.payload.data.count,
      })

    case `${INVOICES_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error
      })

    case `${INVOICE_CREATE}::SUCCESS`:
      return state.set('invoices', state.get('invoices').unshift(action.payload))

    case HIGHLIGHT_INVOICE:
      return state.set('highLightInvoiceId', action.invoiceId)

    default:
      return state
  }
}
