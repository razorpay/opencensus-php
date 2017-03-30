import { merge } from 'rzp/utils/immutable'
import { titleCase } from 'rzp/utils/rzp-utils'

const UPDATE_SESSION = 'UPDATE_SESSION'

export const updateSession = (payload) => {
  return (dispatch) => {
    return dispatch({
      type: UPDATE_SESSION,
      payload
    })
  }
}

let initialState = {
  user: null,
  mode: 'test',
  modeFormatted: 'Test'
}

export default (state = initialState, action) => {
  switch (action.type) {
    case UPDATE_SESSION:
      return merge(state, {
        ...action.payload,
        modeFormatted: titleCase(action.payload.mode)
      })

    default:
      return state;
  }
}
