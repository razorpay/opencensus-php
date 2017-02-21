// kinda `redux-promise` catered to our needs.

const isAsyncAction = (obj) => {
  return obj.type && obj.payload && typeof obj.payload.then === 'function'
}

export default ({ dispatch, getState }) => {
  return (next) => (action) => {
    if (isAsyncAction(action)) {
      dispatch({
        type: `${action.type}::PENDING`,
        extraArgs: action.extraArgs
      })

      return action.payload.then((response) => {
        dispatch({
          type: `${action.type}::SUCCESS`,
          payload: response,
          extraArgs: action.extraArgs
        })
      }).catch((error) => {
        dispatch({
          type: `${action.type}::ERROR`,
          payload: error,
          error: true,
          extraArgs: action.extraArgs
        })
        throw error
      })
    }

    return next(action)
  }
}
