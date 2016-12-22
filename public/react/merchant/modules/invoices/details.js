import { set, merge } from 'rzp/utils/immutable'
import Invoice from 'merchant/models/Invoice'

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

export default function (state = initialState, action) {
  switch(action.type) {
    case `${INVOICE_FETCH}::PENDING`:
      return set(state, 'loading', true)

    case `${INVOICE_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        invoice: action.payload,
        error: null
      })

    case `${INVOICE_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        invoice: initialState.invoice
      })

    case `${SMS_SEND}::SUCCESS`:
      return set(state, 'invoice.sms_status', 'sent')

    case `${EMAIL_SEND}::SUCCESS`:
      return set(state, 'invoice.email_status', 'sent')

    default:
      return state
  }
}
