import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const PLANS_FETCH = 'PLANS_FETCH'
const PLAN_ADDED = 'PLAN_ADDED'
const PLAN_EDITED = 'PLAN_EDITED'

export const fetchPlans = () => {
  return (dispatch) => {
    return dispatch({
      type: PLANS_FETCH,
      payload: ajax('/plans')
    })
  }
}

export const createPlan = (data) => {
  return (dispatch) => {
    return ajax({
      url: '/plan',
      method: 'post',
      data
    })
  }
}

export const editPlan = (id, data) => {
  return (dispatch) => {
    return ajax({
      url: `/plan/${id}`,
      method: 'put',
      data
    })
  }
}


export const planAdded = (plan) => {
  return {
    type: PLAN_ADDED,
    payload: plan
  }
}

export const planEdited = (plan) => {
  return {
    type: PLAN_EDITED,
    payload: plan
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

    case PLAN_ADDED:
      return state.set('plans', state.get('plans').unshift(action.payload))

    case PLAN_EDITED:
      let plans = state.get('plans')
      return state.set('plans', plans.update(
        plans.findIndex((item) => item.get('id') === action.payload.id),
        (item) => item.merge(action.payload)
      ))

    default:
      return state
  }
}
