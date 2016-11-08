import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

let initialState = {
  loading: true,
  invoice: {}
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    default:
      return state
  }
}
