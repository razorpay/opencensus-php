import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'
import subscriptions from 'merchant/mocks/subscriptions'

const SUBSCRIPTIONS_FETCH = 'SUBSCRIPTIONS_FETCH'

export const fetchSubscriptions = () => {
  return (dispatch) => {
    return dispatch({
      type: SUBSCRIPTIONS_FETCH,
      payload: ajax(`/subscriptions`).then((response) => {
      }).catch((err) => {
        return subscriptions
      })
    })
  }
}

let initialState = {
  loading: true,
  subscriptions: [],
  count: 0
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${SUBSCRIPTIONS_FETCH}::PENDING`:
      return state.set('loading', true)

    case `${SUBSCRIPTIONS_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        subscriptions: action.payload.data.items,
        count: action.payload.data.count
      })

    case `${SUBSCRIPTIONS_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error
      })

    default:
      return state
  }
}
