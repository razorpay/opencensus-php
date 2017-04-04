import { set, merge } from 'rzp/utils/immutable'
import Invoice from 'merchant/models/Invoice'
import { INVOICE_CREATE, INVOICE_EDIT } from './list'

const INVOICE_FETCH = 'INVOICE_FETCH'
const SMS_SEND = 'SMS_SEND'
const EMAIL_SEND = 'EMAIL_SEND'
const INVOICE_ISSUE = 'INVOICE_ISSUE'
const INVOICE_INIT = 'INVOICE_INIT'
const INVOICE_CANCEL = 'INVOICE_CANCEL'

export const fetchInvoice = (id) => {
  return (dispatch) => {
    let invoice = new Invoice()
    return dispatch({
      type: INVOICE_FETCH,
      payload: invoice.fetch(id)
    })
  }
}

export const notifyCustomer = (params, type) => {
  return (dispatch) => {
    let invoice = new Invoice(params)
    return dispatch({
      type: type === 'sms' ? SMS_SEND : EMAIL_SEND,
      payload: invoice.notify(type)
    })
  }
}

export const issueInvoice = (params) => {
  return (dispatch) => {
    let invoice = new Invoice(params)
    return dispatch({
      type: INVOICE_ISSUE,
      payload: invoice.markAsIssued()
    })
  }
}

export const initializeInvoice = () => {
  return (dispatch) => {
    return dispatch({
      type: INVOICE_INIT,
      payload: new Invoice(initialState.invoice)
    })
  }
}

export const cancelInvoice = (params) => {
  return (dispatch) => {
    let invoice = new Invoice(params)
    return dispatch({
      type: INVOICE_CANCEL,
      payload: invoice.cancel()
    })
  }
}

let initialState = {
  loading: true,
  invoice: {
    customer_details: {},
    line_items: [],
    notes: {},
  },
  error: null
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${INVOICE_FETCH}::PENDING`:
      return set(state, 'loading', true)

    case `${INVOICE_ISSUE}::SUCCESS`:
    case `${INVOICE_CANCEL}::SUCCESS`:
    case `${INVOICE_FETCH}::SUCCESS`:
    case `${INVOICE_CREATE}::SUCCESS`:
    case `${INVOICE_EDIT}::SUCCESS`:
    case INVOICE_INIT:
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
