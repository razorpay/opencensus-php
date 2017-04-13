import Payment from 'merchant/models/Payment'
import { set, merge } from 'rzp/utils/immutable'

const PAYMENT_FETCH = 'PAYMENT_FETCH'
const PAYMENT_FETCH_CARD_DETAILS = 'PAYMENT_FETCH_CARD_DETAILS'
const PAYMENT_FETCH_REFUNDS = 'PAYMENT_FETCH_REFUNDS'

export const fetchPayment = (id) => {
  return (dispatch) => {
    let payment = new Payment()
    return dispatch({
      type: PAYMENT_FETCH,
      payload: payment.fetch(id)
    })
  }
}

export const fetchCardDetails = (payment) => {
  return (dispatch) => {
    return dispatch({
      type: PAYMENT_FETCH_CARD_DETAILS,
      payload: payment.fetchCardDetails()
    })
  }
}

export const fetchRefunds = (payment) => {
  return (dispatch) => {
    return dispatch({
      type: PAYMENT_FETCH_REFUNDS,
      payload: payment.fetchRefunds()
    })
  }
}

let initialState = {
  loading: true,
  payment: {},
  card: {
    loading: true,
    details: {},
    error: null
  },
  refunds: {
    loading: true,
    items: [],
    error: null
  },
  error: null,
}

export default function (state = initialState, action) {
  switch(action.type) {
    case `${PAYMENT_FETCH}::PENDING`:
      return set(state, 'loading', true)

    case `${PAYMENT_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        payment: action.payload,
        error: null
      })

    case `${PAYMENT_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        payment: initialState.payment
      })

    case `${PAYMENT_FETCH_CARD_DETAILS}::PENDING`:
      return set(state, 'card', {
        loading: true,
        details: {},
        error: null
      })

    case `${PAYMENT_FETCH_CARD_DETAILS}::SUCCESS`:
      return set(state, 'card', {
        loading: false,
        details: action.payload.data,
        error: null
      })

    case `${PAYMENT_FETCH_CARD_DETAILS}::ERROR`:
      return set(state, 'card', {
        loading: false,
        details: {},
        error: action.payload.errors
      })

    case `${PAYMENT_FETCH_REFUNDS}::PENDING`:
      return set(state, 'refunds', {
        loading: true,
        items: [],
        error: null
      })

    case `${PAYMENT_FETCH_REFUNDS}::SUCCESS`:
      return set(state, 'refunds', {
        loading: false,
        items: action.payload.data.items,
        error: null
      })

    case `${PAYMENT_FETCH_REFUNDS}::ERROR`:
      return set(state, 'refunds', {
        loading: false,
        items: [],
        error: action.payload.errors
      })

    default:
      return state
  }
}
