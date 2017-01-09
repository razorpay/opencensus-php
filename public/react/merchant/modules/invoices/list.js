import { set, merge, unshift } from 'rzp/utils/immutable'
import Invoice from 'merchant/models/Invoice'

const INVOICES_FETCH = 'INVOICES_FETCH'
const INVOICE_CREATE = 'INVOICE_CREATE'
const INVOICE_EDIT = 'INVOICE_EDIT'
const INVOICE_DELETED = 'INVOICE_DELETED'
const HIGHLIGHT_INVOICE = 'HIGHLIGHT_INVOICE'
const REMOVE_HIGHLIGHT_INVOICE = 'REMOVE_HIGHLIGHT_INVOICE'

export const fetchInvoices = (params) => {
  return (dispatch) => {
    return dispatch({
      type: INVOICES_FETCH,
      payload: Invoice.fetchAll(params)
    })
  }
}

export const saveInvoice = (invoice) => {
  return (dispatch) => {
    return dispatch({
      type: invoice.isNew ? INVOICE_CREATE : INVOICE_EDIT,
      payload: invoice.save()
    })
  }
}

export const deleteInvoice = (invoice) => {
  return (dispatch) => {
    return invoice.delete().then(() => {
      dispatch({
        type: INVOICE_DELETED,
        payload: invoice
      })
    })
  }
}

export const highLightInvoice = (invoiceId) => {
  return (dispatch) => {
    dispatch({
      type: HIGHLIGHT_INVOICE,
      payload: invoiceId
    })

    setTimeout(() => {
      dispatch({
        type: REMOVE_HIGHLIGHT_INVOICE
      })
    }, 6000)
  }
}

let initialState = {
  loading: true,
  invoices: [],
  count: 0,
  highLightInvoiceId: null
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${INVOICES_FETCH}::PENDING`:
      return set(state, 'loading', true)

    case `${INVOICES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        invoices: action.payload.data.items,
        count: action.payload.data.count,
      })

    case `${INVOICES_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error
      })

    case `${INVOICE_CREATE}::SUCCESS`:
      return set(state, 'invoices', unshift(state.invoices, action.payload))

    case `${INVOICE_EDIT}::SUCCESS`:
      let invoiceIndex = state.invoices.findIndex((invoice) => invoice.id === action.payload.id)
      return set(state, `invoices.${invoiceIndex}`, action.payload)

    case INVOICE_DELETED:
      var invoicesList = remove(state.invoices, (invoice) => invoice.id === action.payload.id)
      return set(state, 'invoices', invoicesList)

    case HIGHLIGHT_INVOICE:
      return set(state, 'highLightInvoiceId', action.payload)

    case REMOVE_HIGHLIGHT_INVOICE:
      return set(state, 'highLightInvoiceId', null)

    default:
      return state
  }
}
