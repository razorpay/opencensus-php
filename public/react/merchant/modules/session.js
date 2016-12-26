import { merge } from 'rzp/utils/immutable'

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
  mode: 'test'
}

export default (state = initialState, action) => {
  switch (action.type) {
    case UPDATE_SESSION:
      return merge(state, action.payload)

    default:
      return state;
  }
}
