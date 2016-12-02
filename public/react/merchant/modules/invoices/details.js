import Invoice from 'merchant/models/Invoice'
import { fromJS } from 'immutable'

const INVOICE_FETCH = 'INVOICE_FETCH'
const SMS_SEND = 'SMS_SEND'
const EMAIL_SEND = 'EMAIL_SEND'

export const fetchInvoice = (id) => {
  return (dispatch) => {
    return dispatch({
      type: INVOICE_FETCH,
      payload: Invoice.fetch(id)
    })
  }
}

export const notifyCustomer = (invoice, type) => {
  return (dispatch) => {
    return dispatch({
      type: type === 'sms' ? SMS_SEND : EMAIL_SEND,
      payload: invoice.notify(type)
    })
  }
}

let initialState = {
  loading: true,
  invoice: {
    customer_details: {},
    line_items: []
  },
  error: null
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${INVOICE_FETCH}::PENDING`:
      return state.set('loading', true)

    case `${INVOICE_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        invoice: action.payload,
        error: null
      })

    case `${INVOICE_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.payload.errors,
        invoice: initialState.invoice
      })

    case `${SMS_SEND}::SUCCESS`:
      var invoice = state.get('invoice')
      invoice.sms_status = 'sent'
      return state.set('invoice', invoice)

    case `${EMAIL_SEND}::SUCCESS`:
      var invoice = state.get('invoice')
      invoice.email_status = 'sent'
      return state.set('invoice', invoice)

    case `${SMS_SEND}::ERROR`:
    case `${EMAIL_SEND}::ERROR`:
      return state.set('error', action.payload.errors)

    default:
      return state
  }
}
