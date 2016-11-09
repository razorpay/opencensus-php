import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'
// import plans from 'merchant/mocks/plans'

const PLANS_FETCH = 'PLANS_FETCH'

export const fetchPlans = () => {
  return (dispatch) => {
    return dispatch({
      type: PLANS_FETCH,
      payload: ajax(`/plans`).then((response) => {
      }).catch((err) => {
        return plans
      })
    })
  }
}

let initialState = {
  loading: true,
  plans: [],
  count: 0
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${PLANS_FETCH}::PENDING`:
      return state.set('loading', true)

    case `${PLANS_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        plans: action.payload.data.items,
        count: action.payload.data.count
      })

    case `${PLANS_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error
      })

    default:
      return state
  }
}
