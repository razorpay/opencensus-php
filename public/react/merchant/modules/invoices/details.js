import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const INVOICE_FETCH = 'INVOICE_FETCH'
const SMS_SEND = 'SMS_SEND'
const EMAIL_SEND = 'EMAIL_SEND'

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

export const notifyCustomer = (id, type) => {
  return (dispatch) => {
    return dispatch({
      type: type === 'sms' ? SMS_SEND : EMAIL_SEND,
      payload: ajax({
        url: `/invoices/${id}/notify/${type}`,
        method: 'post'
      })
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
        invoice: action.payload.data.items[0],
        error: null
      })

    case `${INVOICE_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.payload.errors,
        invoice: initialState.invoice
      })

    case `${SMS_SEND}::SUCCESS`:
      return state.setIn(['invoice', 'sms_status'], 'sent')

    case `${EMAIL_SEND}::SUCCESS`:
      return state.setIn(['invoice', 'email_status'], 'sent')

    case `${SMS_SEND}::ERROR`:
    case `${EMAIL_SEND}::ERROR`:
      return state.set('error', action.payload.errors)

    default:
      return state
  }
}
